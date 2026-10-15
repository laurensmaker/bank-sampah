<?php

namespace App\Services;

use App\Models\Armada;
use App\Models\DetailRute;
use App\Models\OptimasiRute;
use App\Models\PermintaanPenjemputan;
use App\Models\StatusPenjemputan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MTSPService
{
    protected $kapasitasHarian = 25;

    public function proses()
    {
        DB::beginTransaction();

        try {
            Log::info('=== MTSPService: Memulai Proses ===');

            // STEP 1: Buat header optimasi
            $optimasi = OptimasiRute::create([
                'tanggal'           => now()->toDateString(),
                'metode_pembagian'  => 'MTSP',
                'metode_rute'       => 'Nearest Neighbor',
                'jumlah_permintaan' => 0,
                'jumlah_armada'     => 0,
                'total_berat'       => 0,
                'total_jarak'       => 0,
                'total_waktu'       => 0,
            ]);

            // STEP 2: Ambil 2 armada aktif
            $armadas = Armada::where('status_armada', 'Aktif')->take(2)->get();

            if ($armadas->count() < 2) {
                throw new \Exception('Minimal diperlukan 2 armada aktif. Ditemukan: ' . $armadas->count());
            }

            // STEP 3: Update armada dengan id_optimasi
            foreach ($armadas as $armada) {
                $armada->update(['id_optimasirute' => $optimasi->id]);
            }

            // STEP 4: Ambil permintaan dengan status TERKINI "Menunggu"
            // PENTING: pastikan hanya ambil status TERBARU per permintaan
            $permintaan = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu');
            })
            ->with(['statusTerkini', 'nasabah'])
            ->orderBy('tanggal', 'asc')
            ->get();

            Log::info('Jumlah permintaan menunggu: ' . $permintaan->count());

            if ($permintaan->isEmpty()) {
                $optimasi->delete();
                DB::rollBack();
                return (object) [
                    'optimasi' => null,
                    'jumlahDitunda' => 0,
                    'pesan' => 'Tidak ada permintaan dengan status Menunggu.'
                ];
            }

            $armada1 = $armadas[0];
            $armada2 = $armadas[1];

            // ✅ AMBIL KAPASITAS DARI DATABASE (validasi: minimal 1)
            $kapasitasArmada1 = max(1, (float) ($armada1->kapasitas ?? $this->kapasitasHarian));
            $kapasitasArmada2 = max(1, (float) ($armada2->kapasitas ?? $this->kapasitasHarian));

            $kapasitasAwal1 = $kapasitasArmada1;
            $kapasitasAwal2 = $kapasitasArmada2;

            Log::info('Kapasitas Armada 1 (' . $armada1->nama_armada . '): ' . $kapasitasArmada1 . ' kg');
            Log::info('Kapasitas Armada 2 (' . $armada2->nama_armada . '): ' . $kapasitasArmada2 . ' kg');

            $permintaanBesok = [];
            $totalBeratTerbagi = 0;
            $jumlahTerbagi = 0;

            // ✅ PENTING: sortByDesc HARUS di-reset index-nya
            $permintaanSorted = $permintaan->sortByDesc('berat_sampah')->values();

            foreach ($permintaanSorted as $index => $item) {
                $berat = (float) $item->berat_sampah;

                Log::info('--- Memproses permintaan #' . ($index + 1) . ' (ID: ' . $item->id . ') ---');
                Log::info('  Berat: ' . $berat . ' kg');
                Log::info('  Sisa Armada 1: ' . $kapasitasArmada1 . ' kg | Sisa Armada 2: ' . $kapasitasArmada2 . ' kg');

                // Skip jika berat melebihi kapasitas maksimum
                if ($berat > max($kapasitasAwal1, $kapasitasAwal2)) {
                    Log::warning('  ❌ Berat melebihi kapasitas maksimum, dijadwalkan besok');
                    $permintaanBesok[] = $item;
                    continue;
                }

                $jarakArmada1 = $this->haversine(
                    $armada1->latitude_depot, $armada1->longitude_depot,
                    $item->latitude, $item->longitude
                );
                $jarakArmada2 = $this->haversine(
                    $armada2->latitude_depot, $armada2->longitude_depot,
                    $item->latitude, $item->longitude
                );

                $cukup1 = $kapasitasArmada1 >= $berat;
                $cukup2 = $kapasitasArmada2 >= $berat;

                $armadaTerpilih = null;

                if ($cukup1 && $cukup2) {
                    // Pilih yang terdekat
                    $armadaTerpilih = $jarakArmada1 <= $jarakArmada2 ? $armada1 : $armada2;
                } elseif ($cukup1) {
                    $armadaTerpilih = $armada1;
                } elseif ($cukup2) {
                    $armadaTerpilih = $armada2;
                }

                if (!$armadaTerpilih) {
                    Log::info('  ⏳ Kedua armada tidak cukup, dijadwalkan besok');
                    $permintaanBesok[] = $item;
                    continue;
                }

                // ✅ SIMPAN KE DETAIL RUTE
                DetailRute::create([
                    'id_optimasi'    => $optimasi->id,
                    'id_armada'      => $armadaTerpilih->id,
                    'id_permintaan'  => $item->id,
                    'urutan'         => null,
                    'jarak'          => 0,
                    'total_jarak'    => 0,
                    'estimasi_waktu' => 0,
                ]);

                // ✅ KURANGI KAPASITAS — gunakan perbandingan ID yang aman
                if ((int) $armadaTerpilih->id === (int) $armada1->id) {
                    $kapasitasArmada1 -= $berat;
                } else {
                    $kapasitasArmada2 -= $berat;
                }

                StatusPenjemputan::create([
                    'permintaan_id'  => $item->id,
                    'status'         => 'Diproses',
                    'keterangan'     => 'Permintaan telah dibagi ke armada ' . $armadaTerpilih->nama_armada,
                    'tanggal_status' => now()->toDateString(),
                ]);

                $item->update(['id_optimasirute' => $optimasi->id]);

                $totalBeratTerbagi += $berat;
                $jumlahTerbagi++;

                Log::info('  ✅ Masuk ke ' . $armadaTerpilih->nama_armada);
                Log::info('  Sisa Armada 1: ' . $kapasitasArmada1 . ' kg | Sisa Armada 2: ' . $kapasitasArmada2 . ' kg');
            }

            // STEP 6: Update header optimasi
            $optimasi->update([
                'jumlah_armada'     => $armadas->count(),
                'jumlah_permintaan' => $jumlahTerbagi,
                'total_berat'       => round($totalBeratTerbagi, 2),
            ]);

            // STEP 7: Jadwalkan permintaan yang tidak muat untuk besok
            if (!empty($permintaanBesok)) {
                $this->jadwalkanBesok($permintaanBesok);
            }

            DB::commit();

            return (object) [
                'optimasi' => $optimasi,
                'jumlahDitunda' => count($permintaanBesok),
                'pesan' => count($permintaanBesok) > 0
                    ? count($permintaanBesok) . ' permintaan dijadwalkan besok karena kapasitas armada penuh.'
                    : 'Semua permintaan berhasil dibagi.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MTSP Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function jadwalkanBesok($permintaan)
    {
        foreach ($permintaan as $item) {
            StatusPenjemputan::create([
                'permintaan_id'  => $item->id,
                'status'         => 'Menunggu',
                'keterangan'     => 'Dijadwalkan ulang ke besok karena kapasitas armada penuh',
                'tanggal_status' => now()->addDay()->toDateString(),
            ]);
        }
    }

    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}