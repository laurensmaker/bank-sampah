<?php

namespace App\Services;

use App\Models\Armada;
use App\Models\DetailRute;
use App\Models\OptimasiRute;
use App\Models\PermintaanPenjemputan;
use App\Models\StatusPenjemputan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenjemputanService
{
    protected float $kapasitasDefault = 25.0;
    protected float $kecepatanRataRata = 30.0;

    /**
     * ============================================================
     * ENTRY POINT
     * @param Armada $armadaLogin Armada yang menekan tombol optimasi
     * ============================================================
     */
    public function proses(Armada $armadaLogin): object
    {
        DB::beginTransaction();

        try {
            Log::info('=== PenjemputanService: Mulai ===');
            Log::info('Armada prioritas (login): ' . $armadaLogin->nama_armada
                . ' (ID: ' . $armadaLogin->id . ')');

            // STEP 1: Buat header optimasi
            $optimasi = OptimasiRute::create([
                'tanggal'             => now()->toDateString(),
                'id_armada_prioritas' => $armadaLogin->id,
                'metode_pembagian'    => 'MTSP',
                'metode_rute'         => 'Nearest Neighbor',
                'jumlah_permintaan'   => 0,
                'jumlah_armada'       => 0,
                'total_berat'         => 0,
                'total_jarak'         => 0,
                'total_waktu'         => 0,
            ]);

            Log::info('Optimasi dibuat: ID ' . $optimasi->id);

            // STEP 2: Ambil armada pembantu
            $armadaPembantu = Armada::where('status_armada', 'Aktif')
                ->where('id', '!=', $armadaLogin->id)
                ->first();

            if (!$armadaPembantu) {
                throw new \Exception('Minimal diperlukan 2 armada aktif. Armada pembantu tidak ditemukan.');
            }

            // ✅ Armada 1 = yang login (PRIORITAS)
            $armada1 = $armadaLogin;
            $armada2 = $armadaPembantu;

            Log::info('Armada 1 (Prioritas): ' . $armada1->nama_armada);
            Log::info('Armada 2 (Pembantu): ' . $armada2->nama_armada);

            // STEP 3: Ambil permintaan "Menunggu"
            $permintaan = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu')
                ->whereDate('tanggal_status', '<=', now()->toDateString()); // ✅ Tambah filter ini
            })  
            ->whereNull('id_optimasirute')
            ->with(['statusTerkini', 'nasabah'])
            ->orderBy('tanggal', 'asc')
            ->get();

            Log::info('Jumlah permintaan menunggu: ' . $permintaan->count());

            if ($permintaan->isEmpty()) {
                $optimasi->delete();
                DB::rollBack();

                return (object) [
                    'optimasi'      => null,
                    'jumlahDitunda' => 0,
                    'pesan'         => 'Tidak ada permintaan dengan status Menunggu.',
                ];
            }

            // STEP 4: Bagi permintaan ke armada
            $hasilBagi = $this->bagiPermintaanKeArmada($optimasi, $armada1, $armada2, $permintaan);

            Log::info('Distribusi:');
            Log::info('  ' . $armada1->nama_armada . ': ' . $hasilBagi['totalArmada1'] . ' kg');
            Log::info('  ' . $armada2->nama_armada . ': ' . $hasilBagi['totalArmada2'] . ' kg');
            Log::info('  Ditunda: ' . count($hasilBagi['permintaanBesok']));

            // STEP 5: Urutkan rute tiap armada
            $totalJarak = 0;
            $totalWaktu = 0;

            foreach ([$armada1, $armada2] as $armada) {
                $hasilRute = $this->urutkanRuteArmada($armada, $optimasi->id);
                $totalJarak += $hasilRute['totalJarak'];
                $totalWaktu += $hasilRute['totalWaktu'];

                Log::info('Rute ' . $armada->nama_armada . ': '
                    . $hasilRute['jumlahTitik'] . ' titik, '
                    . number_format($hasilRute['totalJarak'], 2) . ' km');
            }

            // STEP 6: Update header
            $optimasi->update([
                'jumlah_armada'     => 2,
                'jumlah_permintaan' => $hasilBagi['jumlahTerbagi'],
                'total_berat'       => round($hasilBagi['totalBeratTerbagi'], 2),
                'total_jarak'       => round($totalJarak, 2),
                'total_waktu'       => (int) $totalWaktu,
            ]);

            // STEP 7: Jadwalkan besok
            if (!empty($hasilBagi['permintaanBesok'])) {
                $this->jadwalkanBesok($hasilBagi['permintaanBesok']);
            }

            DB::commit();
            Log::info('=== PenjemputanService: Selesai ===');

            return (object) [
                'optimasi'      => $optimasi,
                'jumlahDitunda' => count($hasilBagi['permintaanBesok']),
                'pesan'         => count($hasilBagi['permintaanBesok']) > 0
                    ? count($hasilBagi['permintaanBesok']) . ' permintaan dijadwalkan besok.'
                    : 'Semua permintaan berhasil dibagi.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PenjemputanService Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function bagiPermintaanKeArmada(
            OptimasiRute $optimasi,
            Armada $armada1,
            Armada $armada2,
            $permintaan
        ): array {
            // ✅ HITUNG KAPASITAS TERPAKAI HARI INI (dari semua optimasi sebelum ini)
            $terpakaiArmada1 = $this->hitungKapasitasTerpakaiHariIni($armada1);
            $terpakaiArmada2 = $this->hitungKapasitasTerpakaiHariIni($armada2);

            $kapasitasTotal1 = (float) ($armada1->kapasitas ?? $this->kapasitasDefault);
            $kapasitasTotal2 = (float) ($armada2->kapasitas ?? $this->kapasitasDefault);

            // ✅ Sisa kapasitas = total - terpakai hari ini
            $kapasitasArmada1 = max(0, $kapasitasTotal1 - $terpakaiArmada1);
            $kapasitasArmada2 = max(0, $kapasitasTotal2 - $terpakaiArmada2);
            $kapasitasAwal1   = $kapasitasArmada1;
            $kapasitasAwal2   = $kapasitasArmada2;

            Log::info('Kapasitas hari ini:');
            Log::info('  ' . $armada1->nama_armada . ': total ' . $kapasitasTotal1
                . ' kg, terpakai ' . $terpakaiArmada1 . ' kg, sisa ' . $kapasitasArmada1 . ' kg');
            Log::info('  ' . $armada2->nama_armada . ': total ' . $kapasitasTotal2
                . ' kg, terpakai ' . $terpakaiArmada2 . ' kg, sisa ' . $kapasitasArmada2 . ' kg');

            // ✅ Cek apakah kedua armada sudah penuh
            if ($kapasitasArmada1 <= 0 && $kapasitasArmada2 <= 0) {
                Log::warning('Kedua armada sudah penuh! Semua permintaan dijadwalkan besok.');

                // Jadwalkan semua permintaan ke besok
                $semuaPermintaan = $permintaan->values();
                $this->jadwalkanBesok($semuaPermintaan);

                return [
                    'permintaanBesok'   => $semuaPermintaan->all(),
                    'totalBeratTerbagi' => 0,
                    'jumlahTerbagi'     => 0,
                    'totalArmada1'      => 0,
                    'totalArmada2'      => 0,
                    'armada1Penuh'      => true,
                    'armada2Penuh'      => true,
                ];
            }

            // ... lanjutkan dengan loop seperti biasa
            $permintaanSorted = $permintaan->sortByDesc('berat_sampah')->values();
            
            $permintaanBesok   = [];
            $totalBeratTerbagi = 0;
            $jumlahTerbagi     = 0;
            $totalArmada1      = 0;
            $totalArmada2      = 0;

            foreach ($permintaanSorted as $index => $item) {
                $berat = (float) $item->berat_sampah;

                Log::info('--- Permintaan #' . ($index + 1) . ' (ID ' . $item->id . ') ---');
                Log::info('  Berat: ' . $berat . ' kg');
                Log::info('  Sisa A1: ' . $kapasitasArmada1 . ' kg | Sisa A2: ' . $kapasitasArmada2 . ' kg');

                // Skip jika berat > kapasitas awal salah satu armada
                if ($berat > max($kapasitasTotal1, $kapasitasTotal2)) {
                    Log::warning('  ❌ Melebihi kapasitas maksimum, ditunda.');
                    $permintaanBesok[] = $item;
                    continue;
                }

                $cukup1 = $kapasitasArmada1 >= $berat;
                $cukup2 = $kapasitasArmada2 >= $berat;

                $armadaTerpilih = null;

                if ($cukup1 && $cukup2) {
                    $armadaTerpilih = $armada1;
                    Log::info('  → A1 (prioritas) dipilih.');
                } elseif ($cukup1) {
                    $armadaTerpilih = $armada1;
                } elseif ($cukup2) {
                    $armadaTerpilih = $armada2;
                    Log::info('  → A1 penuh, pindah ke A2.');
                } else {
                    Log::info('  ⏳ Keduanya tidak cukup, ditunda ke besok.');
                    $permintaanBesok[] = $item;
                    continue;
                }

                // Simpan ke DetailRute
                DetailRute::create([
                    'id_optimasi'    => $optimasi->id,
                    'id_armada'      => $armadaTerpilih->id,
                    'id_permintaan'  => $item->id,
                    'urutan'         => null,
                    'jarak'          => 0,
                    'total_jarak'    => 0,
                    'estimasi_waktu' => 0,
                ]);

                // Kurangi kapasitas
                if ((int) $armadaTerpilih->id === (int) $armada1->id) {
                    $kapasitasArmada1 -= $berat;
                    $totalArmada1     += $berat;
                } else {
                    $kapasitasArmada2 -= $berat;
                    $totalArmada2     += $berat;
                }

                // Update status → Diproses
                StatusPenjemputan::create([
                    'permintaan_id'  => $item->id,
                    'status'         => 'Diproses',
                    'keterangan'     => 'Dibagi ke armada ' . $armadaTerpilih->nama_armada,
                    'tanggal_status' => now()->toDateString(),
                ]);

                $item->update(['id_optimasirute' => $optimasi->id]);

                $totalBeratTerbagi += $berat;
                $jumlahTerbagi++;

                Log::info('  ✅ Masuk ke ' . $armadaTerpilih->nama_armada);
            }

            return [
                'permintaanBesok'   => $permintaanBesok,
                'totalBeratTerbagi' => $totalBeratTerbagi,
                'jumlahTerbagi'     => $jumlahTerbagi,
                'totalArmada1'      => $totalArmada1,
                'totalArmada2'      => $totalArmada2,
                'armada1Penuh'      => $kapasitasArmada1 <= 0,
                'armada2Penuh'      => $kapasitasArmada2 <= 0,
            ];
    }

    /**
     * ============================================================
     * BAGIAN B: Urutkan rute (Nearest Neighbor)
     * ============================================================
     */
    private function urutkanRuteArmada(Armada $armada, int $idOptimasi): array
    {
        Log::info('=== urutkanRuteArmada ===');
        Log::info('Armada: ' . $armada->nama_armada . ' (ID: ' . $armada->id . ')');
        Log::info('Optimasi ID: ' . $idOptimasi);

        // ✅ STEP 1: Ambil SEMUA ID optimasi HARI INI
        $optimasiHariIniIds = OptimasiRute::whereDate('tanggal', now()->toDateString())
            ->pluck('id')
            ->toArray();

        Log::info('Optimasi hari ini: ' . implode(', ', $optimasiHariIniIds));

        if (empty($optimasiHariIniIds)) {
            return ['totalJarak' => 0, 'totalWaktu' => 0, 'jumlahTitik' => 0];
        }

        // ✅ STEP 2: Ambil titik yang BELUM diurutkan (dari SEMUA optimasi hari ini)
        $belumDikunjungi = DetailRute::with('permintaan')
            ->whereIn('id_optimasi', $optimasiHariIniIds)
            ->where('id_armada', $armada->id)
            ->whereNull('urutan')
            ->get();

        if ($belumDikunjungi->isEmpty()) {
            Log::info('Tidak ada titik untuk diurutkan (semua sudah punya urutan).');
            return ['totalJarak' => 0, 'totalWaktu' => 0, 'jumlahTitik' => 0];
        }

        Log::info('Titik belum diurutkan: ' . $belumDikunjungi->count());

        // ✅ STEP 3: Cari urutan tertinggi HARI INI
        $urutanTerakhir = DetailRute::whereIn('id_optimasi', $optimasiHariIniIds)
            ->where('id_armada', $armada->id)
            ->whereNotNull('urutan')
            ->max('urutan') ?? 0;

        Log::info('Urutan tertinggi saat ini: ' . $urutanTerakhir);

        // ✅ STEP 4: Posisi awal
        $latSekarang = $armada->latitude_depot;
        $lngSekarang = $armada->longitude_depot;

        if ($urutanTerakhir > 0) {
            $titikTerakhir = DetailRute::with('permintaan')
                ->whereIn('id_optimasi', $optimasiHariIniIds)
                ->where('id_armada', $armada->id)
                ->where('urutan', $urutanTerakhir)
                ->first();

            if ($titikTerakhir && $titikTerakhir->permintaan) {
                $latSekarang = $titikTerakhir->permintaan->latitude;
                $lngSekarang = $titikTerakhir->permintaan->longitude;

                Log::info('Mulai dari: ' . ($titikTerakhir->permintaan->nasabah->nama_nasabah ?? '-')
                    . ' (urutan ' . $urutanTerakhir . ')');
            }
        } else {
            Log::info('Mulai dari: DEPOT');
        }

        $urutan = $urutanTerakhir + 1;
        $totalJarak = 0.0;
        $totalWaktu = 0;

        Log::info('Urutan akan dimulai dari: ' . $urutan);

        // ✅ STEP 5: Loop Nearest Neighbor
        while ($belumDikunjungi->isNotEmpty()) {
            $jarakTerdekat = PHP_FLOAT_MAX;
            $titikTerpilih = null;

            foreach ($belumDikunjungi as $detail) {
                if (!$detail->permintaan) continue;

                $lat = $detail->permintaan->latitude;
                $lng = $detail->permintaan->longitude;

                if ($lat === null || $lng === null) continue;

                $jarak = $this->haversine($latSekarang, $lngSekarang, $lat, $lng);

                if ($jarak < $jarakTerdekat) {
                    $jarakTerdekat = $jarak;
                    $titikTerpilih = $detail;
                }
            }

            if ($titikTerpilih === null) break;

            $totalJarak += $jarakTerdekat;
            $estimasiWaktu = (int) round(($jarakTerdekat / $this->kecepatanRataRata) * 60);
            $totalWaktu += $estimasiWaktu;

            $titikTerpilih->update([
                'urutan'         => $urutan,
                'jarak'          => round($jarakTerdekat, 2),
                'total_jarak'    => round($totalJarak, 2),
                'estimasi_waktu' => $estimasiWaktu,
            ]);

            Log::info('  ✅ Urutan ' . $urutan . ': ' 
                . ($titikTerpilih->permintaan->nasabah->nama_nasabah ?? '-')
                . ' | ' . number_format($jarakTerdekat, 2) . ' km');

            StatusPenjemputan::create([
                'permintaan_id'  => $titikTerpilih->id_permintaan,
                'status'         => 'Dijadwalkan',
                'keterangan'     => 'Urutan ke-' . $urutan . ' oleh ' . $armada->nama_armada,
                'tanggal_status' => now()->toDateString(),
            ]);

            // Pindah posisi
            $latSekarang = $titikTerpilih->permintaan->latitude;
            $lngSekarang = $titikTerpilih->permintaan->longitude;

            // Buang dari daftar
            $belumDikunjungi = $belumDikunjungi->reject(
                fn ($it) => $it->id === $titikTerpilih->id
            );

            $urutan++;
        }

        $jumlahTitik = $urutan - $urutanTerakhir - 1;

        Log::info('Selesai: ' . $jumlahTitik . ' titik diurutkan');

        return [
            'totalJarak'  => $totalJarak,
            'totalWaktu'  => $totalWaktu,
            'jumlahTitik' => $jumlahTitik,
        ];
    }

    private function jadwalkanBesok($permintaan): void
    {
        foreach ($permintaan as $item) {
            // ✅ Pastikan tidak ada id_optimasirute yang nyangkut
            $item->update(['id_optimasirute' => null]);

            $sudahDijadwalkan = StatusPenjemputan::where('permintaan_id', $item->id)
                ->where('status', 'Menunggu')
                ->whereDate('tanggal_status', '>', now()->toDateString())
                ->exists();

            if ($sudahDijadwalkan) {
                continue;
            }

            StatusPenjemputan::create([
                'permintaan_id'  => $item->id,
                'status'         => 'Menunggu',
                'keterangan'     => 'Dijadwalkan besok karena kapasitas armada penuh',
                'tanggal_status' => now()->addDay()->toDateString(),
            ]);

            Log::info('  📅 Permintaan ID ' . $item->id . ' dijadwalkan besok');
        }
    }

    /**
     * Haversine — jarak km
     */
    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * ✅ Hitung total berat yang SUDAH diproses armada ini HARI INI
     *    dari semua optimasi yang sudah ada
     */
    private function hitungKapasitasTerpakaiHariIni(Armada $armada): float
    {
        return (float) DetailRute::whereHas('optimasiRute', function ($q) {
                $q->whereDate('tanggal', now()->toDateString());
            })
            ->where('id_armada', $armada->id)
            ->whereHas('permintaan', function ($q) {
                // ✅ Hanya hitung yang BELUM selesai/dibatalkan
                $q->whereDoesntHave('statusTerkini', function ($sq) {
                    $sq->whereIn('status', ['Selesai', 'Dibatalkan']);
                });
            })
            ->get()
            ->sum(fn ($d) => $d->permintaan->berat_sampah ?? 0);
    }
}