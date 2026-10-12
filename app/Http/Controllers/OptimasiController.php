<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\DetailRute;
use App\Models\OptimasiRute;
use App\Models\PermintaanPenjemputan;
use App\Services\MTSPService;
use App\Services\NearestNeighborService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OptimasiController extends Controller
{
    protected $mtsp;
    protected $nn;

    public function __construct(MTSPService $mtsp, NearestNeighborService $nn)
    {
        $this->mtsp = $mtsp;
        $this->nn   = $nn;
    }

    // ─────────────────────────────────────────────────────────────
    //  INDEX — Halaman utama, tampilkan optimasi terakhir
    // ─────────────────────────────────────────────────────────────
    public function index()
    {
        // Optimasi terakhir yang sudah selesai
        $optimasi = OptimasiRute::latest()->first();

        $rutes  = null;
        $armada = null;

        if ($optimasi) {
            $rutes = $this->ambilRutes($optimasi->id);
            $armada = $rutes->first()?->first()?->armada;
        }

        // Hitung permintaan menunggu untuk info di tombol
        $jumlahMenunggu = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
            $q->where('status', 'Menunggu');
        })->count();

        return view('frontend.armada.optimasi', compact('optimasi', 'rutes', 'armada', 'jumlahMenunggu'));
    }

    // ─────────────────────────────────────────────────────────────
    //  PROSES — Jalankan MTSP + Nearest Neighbor (AJAX)
    // ─────────────────────────────────────────────────────────────
    public function proses()
    {
        try {
            Log::info('OptimasiController: Memulai proses optimasi');

            // Cek dulu apakah ada permintaan menunggu
            $adaPermintaan = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu');
            })->exists();

            if (!$adaPermintaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada permintaan dengan status Menunggu.'
                ]);
            }

            // Cek apakah ada armada aktif (minimal 2)
            $adaArmada = Armada::where('status_armada', 'Aktif')->count();

            if ($adaArmada < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal diperlukan 2 armada aktif yang tersedia.'
                ]);
            }

            // Langkah 1: Bagi permintaan ke armada (MTSP)
            $result = $this->mtsp->proses();

            if (!$result || !isset($result->optimasi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses pembagian rute.'
                ]);
            }

            // Langkah 2: Urutkan rute tiap armada (Nearest Neighbor)
            $this->nn->proses($result->optimasi->id);

            Log::info('Optimasi berhasil', [
                'optimasi_id' => $result->optimasi->id,
                'jumlah_permintaan' => $result->optimasi->jumlah_permintaan,
                'jumlah_armada' => $result->optimasi->jumlah_armada
            ]);

            return response()->json([
                'success' => true,
                'message' => $result->pesan ?? 'Optimasi rute berhasil!',
                'redirect' => route('armada.rute'),
                'data' => [
                    'optimasi_id' => $result->optimasi->id,
                    'jumlah_permintaan' => $result->optimasi->jumlah_permintaan,
                    'jumlah_armada' => $result->optimasi->jumlah_armada,
                    'total_berat' => $result->optimasi->total_berat
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error optimasi: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan optimasi: ' . $e->getMessage()
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  SHOW — Detail satu hasil optimasi by ID
    // ─────────────────────────────────────────────────────────────
    public function show($id)
    {
        $optimasi = OptimasiRute::findOrFail($id);
        $rutes    = $this->ambilRutes($id);
        $armada   = $rutes->first()?->first()?->armada;

        return view('frontend.armada.optimasi', compact('optimasi', 'rutes', 'armada'));
    }

    // ─────────────────────────────────────────────────────────────
    //  HISTORY — Daftar semua hasil optimasi
    // ─────────────────────────────────────────────────────────────
    public function history()
    {
        $histories = OptimasiRute::withCount('detailRute')
            ->latest()
            ->paginate(10);

        return view('frontend.armada.optimasi', compact('histories'));
    }

    // ─────────────────────────────────────────────────────────────
    //  GET PERMINTAAN MENUNGGU (Untuk Dashboard)
    // ─────────────────────────────────────────────────────────────
    public function getPermintaanMenunggu()
    {
        try {
            $jumlah = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu');
            })->count();

            return response()->json([
                'success' => true,
                'jumlah' => $jumlah
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE: Ambil rute + relasi lengkap, dikelompokkan per armada
    // ─────────────────────────────────────────────────────────────
    private function ambilRutes($idOptimasi)
    {
        return DetailRute::with([
                'armada',
                'permintaan',
                'permintaan.nasabah',
                'permintaan.statusTerkini'
            ])
            ->where('id_optimasi', $idOptimasi)
            ->orderBy('id_armada')
            ->orderBy('urutan')
            ->get()
            ->groupBy('id_armada');
    }
}