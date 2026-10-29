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
    // protected $mtsp;
    // protected $nn;

    // public function __construct(MTSPService $mtsp, NearestNeighborService $nn)
    // {
    //     $this->mtsp = $mtsp;
    //     $this->nn   = $nn;
    // }

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
   

    public function proses(Request $request, PenjemputanService $service)
    {
        try {
            $hasil = $service->proses();

            if ($hasil->optimasi === null) {
                return back()->with('warning', $hasil->pesan);
            }

            return redirect()
                ->route('optimasi.detail', $hasil->optimasi->id)
                ->with('success', $hasil->pesan);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
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