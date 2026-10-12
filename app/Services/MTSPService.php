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

            Log::info('Optimasi dibuat: ID ' . $optimasi->id);

            // STEP 2: Ambil 2 armada aktif
            $armadas = Armada::where('status_armada', 'Aktif')->take(2)->get();

            Log::info('Jumlah armada aktif: ' . $armadas->count());

            if ($armadas->count() < 2) {
                throw new \Exception('Minimal diperlukan 2 armada aktif. Ditemukan: ' . $armadas->count());
            }

            // STEP 3: Update armada dengan id_optimasi
            foreach ($armadas as $armada) {
                $armada->update(['id_optimasirute' => $optimasi->id]);
                Log::info('Armada ' . $armada->nama_armada . ' diupdate dengan optimasi ID ' . $optimasi->id);
            }

            // STEP 4: Ambil permintaan dengan status "Menunggu"
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
                
                Log::warning('Tidak ada permintaan dengan status Menunggu.');
                
                return (object) [
                    'optimasi' => null,
                    'jumlahDitunda' => 0,
                    'pesan' => 'Tidak ada permintaan dengan status Menunggu.'
                ];
            }

            $armada1 = $armadas[0];
            $armada2 = $armadas[1];
            
            // ✅ AMBIL KAPASITAS DARI DATABASE
            $kapasitasArmada1 = $armada1->kapasitas ?? $this->kapasitasHarian;
            $kapasitasArmada2 = $armada2->kapasitas ?? $this->kapasitasHarian;
            
            // ✅ SIMPAN KAPASITAS AWAL
            $kapasitasAwal1 = $kapasitasArmada1;
            $kapasitasAwal2 = $kapasitasArmada2;
            
            Log::info('Kapasitas Armada 1 (' . $armada1->nama_armada . '): ' . $kapasitasArmada1 . ' kg');
            Log::info('Kapasitas Armada 2 (' . $armada2->nama_armada . '): ' . $kapasitasArmada2 . ' kg');
            
            $permintaanBesok = [];
            $totalBeratTerbagi = 0;
            $jumlahTerbagi = 0;

            // ✅ STEP 5: URUTKAN PERMINTAAN BERDASARKAN BERAT (TERBESAR DULU)
            $permintaanSorted = $permintaan->sortByDesc('berat_sampah');
            
            Log::info('Permintaan diurutkan berdasarkan berat terbesar');

            foreach ($permintaanSorted as $index => $item) {
                Log::info('--- Memproses permintaan #' . ($index + 1) . ' (ID: ' . $item->id . ') ---');
                Log::info('  Berat: ' . $item->berat_sampah . ' kg');
                Log::info('  Lokasi: ' . $item->latitude . ', ' . $item->longitude);

                // Jika berat melebihi kapasitas maksimum, skip
                if ($item->berat_sampah > max($kapasitasAwal1, $kapasitasAwal2)) {
                    Log::warning('  ❌ Berat melebihi kapasitas maksimum: ' . $item->berat_sampah . ' kg');
                    continue;
                }

                // ✅ HITUNG JARAK KE KEDUA ARMADA
                $jarakArmada1 = $this->haversine(
                    $armada1->latitude_depot,
                    $armada1->longitude_depot,
                    $item->latitude,
                    $item->longitude
                );

                $jarakArmada2 = $this->haversine(
                    $armada2->latitude_depot,
                    $armada2->longitude_depot,
                    $item->latitude,
                    $item->longitude
                );

                Log::info('  Jarak ke Armada 1: ' . number_format($jarakArmada1, 2) . ' km');
                Log::info('  Jarak ke Armada 2: ' . number_format($jarakArmada2, 2) . ' km');

                // ✅ CEK SISA KAPASITAS MASING-MASING
                $sisa1 = $kapasitasArmada1;
                $sisa2 = $kapasitasArmada2;
                
                Log::info('  Sisa kapasitas Armada 1: ' . $sisa1 . ' kg');
                Log::info('  Sisa kapasitas Armada 2: ' . $sisa2 . ' kg');

                // ✅ TENTUKAN ARMADA BERDASARKAN JARAK DAN KAPASITAS
                $armadaTerpilih = null;
                
                // Jika kedua armada cukup kapasitas, pilih yang terdekat
                if ($sisa1 >= $item->berat_sampah && $sisa2 >= $item->berat_sampah) {
                    $armadaTerpilih = $jarakArmada1 <= $jarakArmada2 ? $armada1 : $armada2;
                    Log::info('  Kedua armada cukup, pilih yang terdekat: ' . $armadaTerpilih->nama_armada);
                }
                // Jika hanya armada 1 yang cukup
                elseif ($sisa1 >= $item->berat_sampah) {
                    $armadaTerpilih = $armada1;
                    Log::info('  Hanya Armada 1 yang cukup');
                }
                // Jika hanya armada 2 yang cukup
                elseif ($sisa2 >= $item->berat_sampah) {
                    $armadaTerpilih = $armada2;
                    Log::info('  Hanya Armada 2 yang cukup');
                }
                // Jika kedua armada tidak cukup
                else {
                    Log::info('  ⏳ Kedua armada tidak cukup, dijadwalkan besok');
                    $permintaanBesok[] = $item;
                    continue;
                }

                // ✅ SIMPAN KE DETAIL RUTE
                if ($armadaTerpilih) {
                    $detail = DetailRute::create([
                        'id_optimasi'    => $optimasi->id,
                        'id_armada'      => $armadaTerpilih->id,
                        'id_permintaan'  => $item->id,
                        'urutan'         => null,
                        'jarak'          => 0,
                        'total_jarak'    => 0,
                        'estimasi_waktu' => 0,
                    ]);

                    Log::info('  ✅ Detail rute dibuat: ID ' . $detail->id);

                    // Kurangi kapasitas armada terpilih
                    if ($armadaTerpilih->id == $armada1->id) {
                        $kapasitasArmada1 -= $item->berat_sampah;
                    } else {
                        $kapasitasArmada2 -= $item->berat_sampah;
                    }

                    // Update status permintaan menjadi "Diproses"
                    StatusPenjemputan::create([
                        'permintaan_id'  => $item->id,
                        'status'         => 'Diproses',
                        'keterangan'     => 'Permintaan telah dibagi ke armada ' . $armadaTerpilih->nama_armada,
                        'tanggal_status' => now()->toDateString(),
                    ]);

                    $item->update(['id_optimasirute' => $optimasi->id]);

                    $totalBeratTerbagi += $item->berat_sampah;
                    $jumlahTerbagi++;

                    Log::info('  ✅ Permintaan berhasil dibagi ke ' . $armadaTerpilih->nama_armada);
                    Log::info('  Sisa kapasitas Armada 1: ' . $kapasitasArmada1 . ' kg');
                    Log::info('  Sisa kapasitas Armada 2: ' . $kapasitasArmada2 . ' kg');
                }
            }

            // STEP 6: Update header optimasi
            $optimasi->update([
                'jumlah_armada'     => $armadas->count(),
                'jumlah_permintaan' => $jumlahTerbagi,
                'total_berat'       => round($totalBeratTerbagi, 2),
            ]);

            Log::info('=== Optimasi diupdate ===');
            Log::info('  Jumlah permintaan terbagi: ' . $jumlahTerbagi);
            Log::info('  Total berat: ' . round($totalBeratTerbagi, 2) . ' kg');

            // STEP 7: Jadwalkan permintaan yang tidak muat untuk besok
            if (!empty($permintaanBesok)) {
                $this->jadwalkanBesok($permintaanBesok);
                Log::info('  Jumlah permintaan ditunda: ' . count($permintaanBesok));
            }

            // ✅ STEP 8: CEK DISTRIBUSI PER ARMADA
            $detailPerArmada = DetailRute::where('id_optimasi', $optimasi->id)
                ->select('id_armada', DB::raw('COUNT(*) as total'))
                ->groupBy('id_armada')
                ->get();

            Log::info('=== Distribusi per armada ===');
            foreach ($detailPerArmada as $da) {
                $armada = Armada::find($da->id_armada);
                Log::info('  ' . ($armada->nama_armada ?? 'Unknown') . ': ' . $da->total . ' permintaan');
            }

            DB::commit();

            Log::info('=== MTSPService: Proses Selesai ===');

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

    /**
     * Jadwalkan permintaan untuk besok
     */
    private function jadwalkanBesok($permintaan)
    {
        foreach ($permintaan as $item) {
            StatusPenjemputan::create([
                'permintaan_id'  => $item->id,
                'status'         => 'Menunggu',
                'keterangan'     => 'Dijadwalkan ulang ke besok karena kapasitas armada penuh',
                'tanggal_status' => now()->addDay()->toDateString(),
            ]);
            
            Log::info('  📅 Permintaan ID ' . $item->id . ' dijadwalkan ulang ke besok');
        }
    }

    /**
     * Haversine — kembalikan jarak dalam km
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
}