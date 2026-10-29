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
    // Kapasitas maksimal per armada per hari (kg)
    protected $kapasitasHarian = 25;

    /**
     * Proses pembagian. $prioritasArmadaId = id armada yang men-trigger optimasi (optional)
     */
    public function proses($prioritasArmadaId = null)
    {
        DB::beginTransaction();

        try {
            Log::info('=== MTSPService: Memulai Proses ===');
            Log::info('Prioritas Armada ID: ' . ($prioritasArmadaId ?? 'null'));

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

            // Ambil kapasitas dari DB tetapi batasi ke kapasitas harian maksimum
            $kapasitasArmada1 = min($this->kapasitasHarian, max(1, (float) ($armada1->kapasitas ?? $this->kapasitasHarian)));
            $kapasitasArmada2 = min($this->kapasitasHarian, max(1, (float) ($armada2->kapasitas ?? $this->kapasitasHarian)));

            $kapasitasAwal1 = $kapasitasArmada1;
            $kapasitasAwal2 = $kapasitasArmada2;

            Log::info('Kapasitas Armada 1 (' . $armada1->nama_armada . '): ' . $kapasitasArmada1 . ' kg (max per hari: ' . $this->kapasitasHarian . ' kg)');
            Log::info('Kapasitas Armada 2 (' . $armada2->nama_armada . '): ' . $kapasitasArmada2 . ' kg (max per hari: ' . $this->kapasitasHarian . ' kg)');

            $permintaanBesok = [];
            $totalBeratTerbagi = 0;
            $jumlahTerbagi = 0;

            // Reset index setelah sortByDesc
            $permintaanSorted = $permintaan->sortByDesc('berat_sampah')->values();

            foreach ($permintaanSorted as $index => $item) {
                $berat = (float) $item->berat_sampah;

                Log::info('--- Memproses permintaan #' . ($index + 1) . ' (ID: ' . $item->id . ') ---');
                Log::info('  Berat: ' . $berat . ' kg');
                Log::info('  Sisa Armada 1: ' . $kapasitasArmada1 . ' kg | Sisa Armada 2: ' . $kapasitasArmada2 . ' kg');

                // Jika satu permintaan lebih besar dari kapasitas maksimal per hari, jadwalkan besok
                if ($berat > $this->kapasitasHarian) {
                    Log::warning('  ❌ Berat melebihi kapasitas maksimum per hari (' . $this->kapasitasHarian . ' kg), dijadwalkan besok');
                    $permintaanBesok[] = $item;
                    continue;
                }

                // Hitung jarak dari depot masing-masing armada
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

                // Tentukan armada prioritas dari parameter
                $prioritas = null;
                if ($prioritasArmadaId !== null) {
                    if ((int) $prioritasArmadaId === (int) $armada1->id) {
                        $prioritas = $armada1;
                    } elseif ((int) $prioritasArmadaId === (int) $armada2->id) {
                        $prioritas = $armada2;
                    }
                }

                if ($cukup1 && $cukup2) {
                    // Jika ada prioritas dan prioritas termasuk armada saat ini dan punya kapasitas -> pilih prioritas
                    if ($prioritas !== null) {
                        if ((int) $prioritas->id === (int) $armada1->id && $cukup1) {
                            $armadaTerpilih = $armada1;
                        } elseif ((int) $prioritas->id === (int) $armada2->id && $cukup2) {
                            $armadaTerpilih = $armada2;
                        } else {
                            // Prioritas tidak punya kapasitas, fallback ke jarak
                            $armadaTerpilih = $jarakArmada1 <= $jarakArmada2 ? $armada1 : $armada2;
                        }
                    } else {
                        // Tidak ada prioritas -> pakai jarak terdekat
                        $armadaTerpilih = $jarakArmada1 <= $jarakArmada2 ? $armada1 : $armada2;
                    }
                } elseif ($cukup1) {
                    $armadaTerpilih = $armada1;
                } elseif ($cukup2) {
                    $armadaTerpilih = $armada2;
                }

                if (!$armadaTerpilih) {
                    Log::info('  ⏳ Kedua armada tidak cukup sisa kapasitas untuk permintaan ini, dijadwalkan besok');
                    $permintaanBesok[] = $item;
                    continue;
                }

                // SIMPAN KE DETAIL RUTE
                DetailRute::create([
                    'id_optimasi'    => $optimasi->id,
                    'id_armada'      => $armadaTerpilih->id,
                    'id_permintaan'  => $item->id,
                    'urutan'         => null,
                    'jarak'          => 0,
                    'total_jarak'    => 0,
                    'estimasi_waktu' => 0,
                ]);

                // KURANGI KAPASITAS — gunakan perbandingan ID yang aman
                if ((int) $armadaTerpilih->id === (int) $armada1->id) {
                    $kapasitasArmada1 -= $berat;
                    $kapasitasArmada1 = max(0, $kapasitasArmada1);
                } else {
                    $kapasitasArmada2 -= $berat;
                    $kapasitasArmada2 = max(0, $kapasitasArmada2);
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
                    ? count($permintaanBesok) . ' permintaan dijadwalkan besok karena kapasitas armada penuh atau permintaan terlalu besar.'
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