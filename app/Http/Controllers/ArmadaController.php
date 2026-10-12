<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\DetailRute;
use App\Models\OptimasiRute;
use App\Models\PermintaanPenjemputan;
use App\Models\StatusPenjemputan;
use App\Services\MTSPService;
use App\Services\NearestNeighborService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArmadaController extends Controller
{
    protected $nn;
    protected $mtsp;

    public function __construct(NearestNeighborService $nn, MTSPService $mtsp)
    {
        $this->nn = $nn;
        $this->mtsp = $mtsp;
    }

    /**
     * Dashboard Armada
     */
    /**
 * Dashboard Armada
 */
   public function index()
    {
        try {
            $user = Auth::user();
            $armada = Armada::where('email', $user->email)->first();

            if (!$armada) {
                return redirect()->route('login')->with('error', 'Data armada tidak ditemukan.');
            }

            // Cari optimasi hari ini
            $optimasiHariIni = OptimasiRute::whereDate('tanggal', now()->toDateString())
                ->whereHas('detailRute', function ($q) use ($armada) {
                    $q->where('id_armada', $armada->id);
                })
                ->first();

            $tugas = collect();
            $totalTugas = 0;
            $totalSelesai = 0;
            $totalJarak = 0;
            $optimasiAktif = false;
            $kapasitasTerpakai = 0;
            $sisaKapasitas = 0;

            if ($optimasiHariIni) {
                $tugas = DetailRute::with([
                    'permintaan',
                    'permintaan.nasabah',
                    'permintaan.statusTerkini'
                ])
                ->where('id_optimasi', $optimasiHariIni->id)
                ->where('id_armada', $armada->id)
                ->orderBy('urutan', 'asc')
                ->get();

                $totalTugas = $tugas->count();
                $totalSelesai = $tugas->filter(function ($item) {
                    return $item->permintaan->statusTerkini && 
                        $item->permintaan->statusTerkini->status === 'Selesai';
                })->count();
                $totalJarak = $tugas->sum('total_jarak');
                $optimasiAktif = true;
                
                // ✅ HITUNG KAPASITAS
                $kapasitasTerpakai = $tugas->sum(function ($item) {
                    return $item->permintaan->berat_sampah ?? 0;
                });
                $sisaKapasitas = ($armada->kapasitas ?? 25) - $kapasitasTerpakai;
                
                Log::info('Armada ' . $armada->nama_armada . ' - Kapasitas terpakai: ' . $kapasitasTerpakai . ' kg, Sisa: ' . $sisaKapasitas . ' kg');
            }

            // ✅ CEK PERMINTAAN YANG DIJADWALKAN BESOK
            $permintaanBesok = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu')
                ->whereDate('tanggal_status', '>', now()->toDateString());
            })->count();

            // Cek apakah ada permintaan menunggu untuk hari ini
            $adaPermintaanMenunggu = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu')
                ->whereDate('tanggal_status', '<=', now()->toDateString());
            })->exists();

            return view('frontend.armada.dasbor', compact(
                'armada',
                'tugas',
                'totalTugas',
                'totalSelesai',
                'totalJarak',
                'optimasiAktif',
                'optimasiHariIni',
                'adaPermintaanMenunggu',
                'kapasitasTerpakai',
                'sisaKapasitas',
                'permintaanBesok'
            ));

        } catch (\Exception $e) {
            Log::error('Error ArmadaController@index: ' . $e->getMessage());
            return view('frontend.armada.dasbor', [
                'armada' => Armada::where('email', Auth::user()->email)->first(),
                'tugas' => collect(),
                'totalTugas' => 0,
                'totalSelesai' => 0,
                'totalJarak' => 0,
                'optimasiAktif' => false,
                'optimasiHariIni' => null,
                'adaPermintaanMenunggu' => false,
                'kapasitasTerpakai' => 0,
                'sisaKapasitas' => 0,
                'permintaanBesok' => 0
            ]);
        }
    }

    /**
     * ✅ TAMPILAN RUTE DI GOOGLE MAPS
     */
    public function rute()
    {
        try {
            $user = Auth::user();
            $armada = Armada::where('email', $user->email)->first();

            if (!$armada) {
                return redirect()->route('login')->with('error', 'Data armada tidak ditemukan.');
            }

            // Cari optimasi hari ini
            $optimasi = OptimasiRute::whereDate('tanggal', now()->toDateString())
                ->whereHas('detailRute', function ($q) use ($armada) {
                    $q->where('id_armada', $armada->id);
                })
                ->first();

            if (!$optimasi) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Belum ada rute penjemputan untuk hari ini.');
            }

            // Ambil detail rute untuk armada ini
            $tugas = DetailRute::with([
                'permintaan',
                'permintaan.nasabah',
                'permintaan.statusTerkini'
            ])
            ->where('id_optimasi', $optimasi->id)
            ->where('id_armada', $armada->id)
            ->orderBy('urutan', 'asc')
            ->get();

            if ($tugas->isEmpty()) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Tidak ada tugas penjemputan untuk hari ini.');
            }

            // Format data untuk Google Maps
            $points = $this->formatPointsForMap($armada, $tugas);

            return view('frontend.armada.rute', compact('armada', 'tugas', 'optimasi', 'points'));

        } catch (\Exception $e) {
            Log::error('Error ArmadaController@rute: ' . $e->getMessage());
            return redirect()->route('armada.dasbor')
                ->with('error', 'Gagal menampilkan rute: ' . $e->getMessage());
        }
    }

    /**
     * ✅ PROSES OPTIMASI VIA GET (Redirect)
     */
    public function optimasiProses()
    {
        try {
            Log::info('=== OPTIMASI PROSES VIA GET DIPANGGIL ===');
            
            // Cek apakah ada permintaan menunggu
            $adaPermintaan = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu');
            })->exists();

            if (!$adaPermintaan) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Tidak ada permintaan dengan status Menunggu.');
            }

            // Cek armada aktif
            $armadaCount = Armada::where('status_armada', 'Aktif')->count();
            if ($armadaCount < 2) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Minimal diperlukan 2 armada aktif.');
            }

            DB::beginTransaction();

            // 1. Proses MTSP
            $result = $this->mtsp->proses();
            
            if (!$result || !isset($result->optimasi)) {
                throw new \Exception('Gagal memproses MTSP.');
            }

            // 2. Proses Nearest Neighbor
            $this->nn->proses($result->optimasi->id);

            DB::commit();

            return redirect()->route('armada.rute')
                ->with('success', $result->pesan ?? 'Optimasi berhasil!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error optimasi proses: ' . $e->getMessage());
            return redirect()->route('armada.dasbor')
                ->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * ✅ PROSES OPTIMASI VIA AJAX (untuk tombol dengan SweetAlert)
     */
    public function optimasiRute()
    {
        try {
            Log::info('=== OPTIMASI RUTE VIA AJAX DIPANGGIL ===');
            
            $adaPermintaan = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu');
            })->exists();

            if (!$adaPermintaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada permintaan dengan status Menunggu.'
                ]);
            }

            $armadaCount = Armada::where('status_armada', 'Aktif')->count();
            if ($armadaCount < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal diperlukan 2 armada aktif.'
                ]);
            }

            DB::beginTransaction();

            $result = $this->mtsp->proses();
            
            if (!$result || !isset($result->optimasi)) {
                throw new \Exception('Gagal memproses MTSP.');
            }

            $this->nn->proses($result->optimasi->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $result->pesan ?? 'Optimasi berhasil!',
                'redirect' => route('armada.rute'),
                'data' => [
                    'optimasi_id' => $result->optimasi->id,
                    'jumlah_permintaan' => $result->optimasi->jumlah_permintaan,
                    'jumlah_armada' => $result->optimasi->jumlah_armada,
                    'total_berat' => $result->optimasi->total_berat
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error optimasi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ UPDATE STATUS MENJADI SELESAI
     */
    public function selesai($idPermintaan)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $armada = Armada::where('email', $user->email)->first();
            
            if (!$armada) {
                throw new \Exception('Data armada tidak ditemukan.');
            }

            $permintaan = PermintaanPenjemputan::findOrFail($idPermintaan);
            
            // Cek apakah permintaan milik armada ini
            $detailRute = DetailRute::where('id_permintaan', $idPermintaan)
                ->where('id_armada', $armada->id)
                ->first();

            if (!$detailRute) {
                throw new \Exception('Permintaan tidak ditemukan atau bukan milik armada Anda.');
            }

            // Cek status terkini
            $statusTerkini = $permintaan->statusTerkini;
            if ($statusTerkini && $statusTerkini->status === 'Selesai') {
                throw new \Exception('Permintaan ini sudah selesai.');
            }

            // Update status menjadi Selesai
            StatusPenjemputan::create([
                'permintaan_id'  => $idPermintaan,
                'status'         => 'Selesai',
                'keterangan'     => 'Penjemputan selesai oleh ' . $armada->nama_armada,
                'tanggal_status' => now()->toDateString(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Status penjemputan berhasil diupdate menjadi Selesai.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error selesai: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * ✅ FORMAT DATA UNTUK GOOGLE MAPS
     */
    private function formatPointsForMap($armada, $tugas)
    {
        $points = [
            'depot' => [
                'lat' => (float) $armada->latitude_depot,
                'lng' => (float) $armada->longitude_depot
            ],
            'tujuan' => []
        ];

        foreach ($tugas as $item) {
            $points['tujuan'][] = [
                'lat' => (float) $item->permintaan->latitude,
                'lng' => (float) $item->permintaan->longitude,
                'nama' => $item->permintaan->nasabah->nama_nasabah ?? 'Nasabah',
                'alamat' => $item->permintaan->alamat_penjemputan,
                'urutan' => $item->urutan,
                'berat' => $item->permintaan->berat_sampah,
                'jenis_sampah' => $item->permintaan->jenis_sampah
            ];
        }

        return $points;
    }
}