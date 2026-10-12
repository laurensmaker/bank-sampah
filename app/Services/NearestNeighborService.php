<?php

namespace App\Services;

use App\Models\Armada;
use App\Models\DetailRute;
use App\Models\OptimasiRute;
use App\Models\StatusPenjemputan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NearestNeighborService
{
    public function proses($idOptimasi)
    {
        DB::beginTransaction();

        try {
            Log::info('=== NearestNeighborService: Memulai Proses ===');
            Log::info('Optimasi ID: ' . $idOptimasi);

            // Cek apakah ada detail rute untuk optimasi ini
            $detailCount = DetailRute::where('id_optimasi', $idOptimasi)->count();
            Log::info('Jumlah detail rute sebelum proses: ' . $detailCount);

            if ($detailCount == 0) {
                Log::warning('Tidak ada detail rute untuk optimasi ID ' . $idOptimasi);
                DB::rollBack();
                return;
            }

            // Semua armada yang punya rute pada optimasi ini
            $armadas = Armada::whereHas('detailRute', function ($q) use ($idOptimasi) {
                $q->where('id_optimasi', $idOptimasi);
            })->get();

            Log::info('Jumlah armada: ' . $armadas->count());

            if ($armadas->isEmpty()) {
                Log::warning('Tidak ada armada yang memiliki rute');
                DB::rollBack();
                return;
            }

            foreach ($armadas as $armada) {
                Log::info('--- Optimasi armada: ' . $armada->nama_armada . ' (ID: ' . $armada->id . ') ---');
                $this->optimasiArmada($armada, $idOptimasi);
            }

            // Hitung total_jarak & total_waktu lalu simpan ke header
            $this->updateHeader($idOptimasi);

            DB::commit();
            Log::info('=== NearestNeighborService: Proses Selesai ===');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('NearestNeighbor Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Nearest Neighbor untuk satu armada
     */
    private function optimasiArmada(Armada $armada, $idOptimasi): void
    {
        $latSekarang = $armada->latitude_depot;
        $lngSekarang = $armada->longitude_depot;
        $urutan = 1;
        $totalJarak = 0;

        // Ambil semua titik yang belum diurutkan milik armada ini
        $belumDikunjungi = DetailRute::with('permintaan')
            ->where('id_optimasi', $idOptimasi)
            ->where('id_armada', $armada->id)
            ->whereNull('urutan')
            ->get();

        Log::info('  Jumlah titik belum diurutkan: ' . $belumDikunjungi->count());

        if ($belumDikunjungi->isEmpty()) {
            Log::warning('  Tidak ada titik untuk armada ' . $armada->nama_armada);
            return;
        }

        while ($belumDikunjungi->isNotEmpty()) {
            $jarakTerdekat = PHP_FLOAT_MAX;
            $titikTerpilih = null;

            // Cari titik terdekat dari posisi sekarang
            foreach ($belumDikunjungi as $detail) {
                // PASTIKAN permintaan dan latitude/longitude ada
                if (!$detail->permintaan) {
                    Log::warning('  Permintaan tidak ditemukan untuk detail ID: ' . $detail->id);
                    continue;
                }

                $lat = $detail->permintaan->latitude;
                $lng = $detail->permintaan->longitude;

                if ($lat === null || $lng === null) {
                    Log::warning('  Koordinat null untuk permintaan ID: ' . $detail->id_permintaan);
                    continue;
                }

                $jarak = $this->haversine(
                    $latSekarang,
                    $lngSekarang,
                    $lat,
                    $lng
                );

                Log::info('    Jarak ke titik ' . $detail->id_permintaan . ': ' . number_format($jarak, 2) . ' km');

                if ($jarak < $jarakTerdekat) {
                    $jarakTerdekat = $jarak;
                    $titikTerpilih = $detail;
                }
            }

            // Jika tidak ada titik terpilih (misal semua koordinat null)
            if ($titikTerpilih === null) {
                Log::warning('  Tidak ada titik yang valid untuk armada ' . $armada->nama_armada);
                break;
            }

            $totalJarak += $jarakTerdekat;

            Log::info('    ✅ Urutan ' . $urutan . ': Permintaan ID ' . $titikTerpilih->id_permintaan . 
                     ', Jarak ' . number_format($jarakTerdekat, 2) . ' km');

            // UPDATE detail rute dengan urutan, jarak, dll
            $titikTerpilih->update([
                'urutan' => $urutan,
                'jarak' => round($jarakTerdekat, 2),
                'total_jarak' => round($totalJarak, 2),
                'estimasi_waktu' => (int) round(($jarakTerdekat / 30) * 60), // 30 km/jam
            ]);

            // Update status permintaan → Dijadwalkan
            StatusPenjemputan::create([
                'permintaan_id' => $titikTerpilih->id_permintaan,
                'status' => 'Dijadwalkan',
                'keterangan' => 'Urutan penjemputan ke-' . $urutan . ' oleh ' . $armada->nama_armada,
                'tanggal_status' => now()->toDateString(),
            ]);

            // Pindah posisi ke titik yang baru dikunjungi
            $latSekarang = $titikTerpilih->permintaan->latitude;
            $lngSekarang = $titikTerpilih->permintaan->longitude;

            // Keluarkan dari daftar
            $belumDikunjungi = $belumDikunjungi->reject(
                fn($item) => $item->id === $titikTerpilih->id
            );

            $urutan++;
        }

        Log::info('  Total jarak armada ' . $armada->nama_armada . ': ' . number_format($totalJarak, 2) . ' km');
        Log::info('  Total titik diproses: ' . ($urutan - 1));
    }

    /**
     * Rekap total_jarak & total_waktu ke header optimasirute
     */
    private function updateHeader($idOptimasi): void
    {
        $semuaDetail = DetailRute::where('id_optimasi', $idOptimasi)->get();

        $totalJarak = $semuaDetail->sum('jarak');
        $totalWaktu = $semuaDetail->sum('estimasi_waktu');

        Log::info('Total jarak semua armada: ' . number_format($totalJarak, 2) . ' km');
        Log::info('Total waktu semua armada: ' . $totalWaktu . ' menit');

        OptimasiRute::where('id', $idOptimasi)->update([
            'total_jarak' => round($totalJarak, 2),
            'total_waktu' => (int) $totalWaktu,
        ]);
    }

    /**
     * Haversine — kembalikan jarak dalam km
     */
    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}