<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\DetailRute;
use App\Models\OptimasiRute;
use App\Models\PermintaanPenjemputan;
use App\Models\StatusPenjemputan;
use App\Services\PenjemputanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArmadaController extends Controller
{
    protected PenjemputanService $service;

    public function __construct(PenjemputanService $service)
    {
        $this->service = $service;
    }

    /**
     * Dashboard armada
     */
    public function index()
    {
        // 1. Ambil user yang sedang login
        $user = Auth::user();
        
        // 2. Cari data armada
        $armada = Armada::where('email', $user->email)->first();

        // 3. PENGAMAN: Jika armada tidak ditemukan, logout dan kembalikan ke login
        if (!$armada) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Data armada tidak ditemukan di sistem.']);
        }

        try {
            // ✅ Cari optimasi TERBARU hari ini
            $optimasiHariIni = OptimasiRute::whereDate('tanggal', now()->toDateString())
                ->orderByDesc('id')
                ->first();

            // ✅ Deklarasi variabel default (PENTING: agar tidak undefined jika tidak ada optimasi)
            $tugas = collect();
            $tugasAktif = collect();
            $tugasSelesai = collect();
            $totalTugas = 0;
            $totalSelesai = 0;
            $totalJarak = 0;
            $optimasiAktif = false;
            $kapasitasTerpakai = 0;
            $sisaKapasitas = 0;
            $isPrioritas = false;
            $armadaPrioritas = null;

            if ($optimasiHariIni) {
                // ✅ Ambil SEMUA tugas armada ini HARI INI
                $semuaTugas = DetailRute::with(['permintaan.nasabah', 'permintaan.statusTerkini'])
                    ->whereHas('optimasiRute', function ($q) {
                        $q->whereDate('tanggal', now()->toDateString());
                    })
                    ->where('id_armada', $armada->id)
                    ->orderBy('id_optimasi', 'asc')
                    ->orderBy('urutan', 'asc')
                    ->get();

                // ✅ PISAHKAN: aktif vs selesai
                $tugasAktif = $semuaTugas->filter(function ($t) {
                    $status = optional($t->permintaan->statusTerkini)->status;
                    return !in_array($status, ['Selesai', 'Dibatalkan']);
                })->values();

                $tugasSelesai = $semuaTugas->filter(function ($t) {
                    $status = optional($t->permintaan->statusTerkini)->status;
                    return in_array($status, ['Selesai', 'Dibatalkan']);
                })->values();

                // ✅ $tugas = semua (untuk backward compat)
                $tugas = $semuaTugas;

                $totalTugas = $semuaTugas->count();
                $totalSelesai = $tugasSelesai->count();
                $totalJarak = $semuaTugas->sum('jarak');
                $optimasiAktif = $tugasAktif->count() > 0;

                // Kapasitas terpakai = hanya yang BELUM selesai
                $kapasitasTerpakai = $tugasAktif->sum(fn ($t) => $t->permintaan->berat_sampah ?? 0);
                $sisaKapasitas = max(0, ($armada->kapasitas ?? 25) - $kapasitasTerpakai);

                // Prioritas
                $isPrioritas = (int) $optimasiHariIni->id_armada_prioritas === (int) $armada->id;
                $armadaPrioritas = $optimasiHariIni->id_armada_prioritas
                    ? Armada::find($optimasiHariIni->id_armada_prioritas)
                    : null;
            }

            $permintaanBesok = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu')
                  ->whereDate('tanggal_status', '>', now()->toDateString());
            })->count();

            $detailPermintaanBesok = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu')
                ->whereDate('tanggal_status', '>', now()->toDateString());
            })
            ->with(['nasabah', 'statusTerkini'])
            ->orderBy('id', 'asc')
            ->get();

            // ✅ Cek apakah ada permintaan "Menunggu" yang BELUM dioptimasi
            $permintaanMenungguBelumDiproses = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                $q->where('status', 'Menunggu')
                ->whereDate('tanggal_status', '<=', now()->toDateString()); // ✅ Tambah filter ini
            })
            ->whereNull('id_optimasirute')
            ->count();;

            // ✅ Tombol optimasi muncul jika ada permintaan baru
            $adaPermintaanMenunggu = $permintaanMenungguBelumDiproses > 0;

            return view('frontend.armada.dasbor', compact(
                'armada', 'tugas', 'tugasAktif', 'tugasSelesai', 'totalTugas', 'totalSelesai', 'totalJarak',
                'optimasiAktif', 'optimasiHariIni', 'adaPermintaanMenunggu',
                'kapasitasTerpakai', 'sisaKapasitas', 'permintaanBesok',
                'isPrioritas', 'armadaPrioritas',
                'permintaanMenungguBelumDiproses', 'detailPermintaanBesok'
            ));

        } catch (\Exception $e) {
            // Tampilkan error jika ada masalah database (Hapus dd() jika sudah production)
            dd('Error di Dasbor Armada: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
    /**
     * ✅ PROSES OPTIMASI — bisa dijalankan BERKALI-KALI per hari
     *    selama masih ada permintaan "Menunggu" yang belum diproses
     */
    public function optimasiProses()
    {
        try {
            Log::info('=== OPTIMASI PROSES DIPANGGIL ===');

            // Ambil armada yang LOGIN
            $user = Auth::user();
            $armadaLogin = Armada::where('email', $user->email)->first();

            if (!$armadaLogin) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Data armada tidak ditemukan.');
            }

            // ✅ PERUBAHAN KUNCI:
            // Cek apakah ada permintaan "Menunggu" yang BELUM dioptimasi
            $permintaanBaru = PermintaanPenjemputan::whereHas('statusTerkini', function ($q) {
                    $q->where('status', 'Menunggu')
                    ->whereDate('tanggal_status', '<=', now()->toDateString()); // ✅ Tambah filter ini
                })
                ->whereNull('id_optimasirute')
                ->count();

            if ($permintaanBaru === 0) {
                return redirect()->route('armada.dasbor')
                    ->with('warning', 'Tidak ada permintaan baru untuk hari ini yang perlu dioptimasi. '
                        . 'Permintaan untuk besok tidak akan ditarik ke hari ini.');
            }

            Log::info('Jumlah permintaan baru: ' . $permintaanBaru);

            // Cek minimal 2 armada aktif
            $armadaCount = Armada::where('status_armada', 'Aktif')->count();
            if ($armadaCount < 2) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Minimal diperlukan 2 armada aktif.');
            }

            // ✅ Panggil service — armada login = prioritas
            $hasil = $this->service->proses($armadaLogin);

            if (!$hasil || !$hasil->optimasi) {
                return redirect()->route('armada.dasbor')
                    ->with('warning', $hasil->pesan ?? 'Tidak ada permintaan yang diproses.');
            }

            $jumlahBaru = $hasil->optimasi->jumlah_permintaan;

            return redirect()->route('armada.rute')
                ->with('success', $jumlahBaru . ' permintaan baru berhasil dioptimasi! '
                    . 'Anda adalah armada prioritas. '
                    . ($hasil->pesan ?? ''));

        } catch (\Exception $e) {
            Log::error('Error optimasi proses: ' . $e->getMessage());
            return redirect()->route('armada.dasbor')
                ->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function rute()
    {
        try {
            $user = Auth::user();
            $armada = Armada::where('email', $user->email)->first();

            if (!$armada) {
                return redirect()->route('login')->with('error', 'Data armada tidak ditemukan.');
            }

            $optimasi = OptimasiRute::whereDate('tanggal', now()->toDateString())
                ->whereHas('detailRute', fn ($q) => $q->where('id_armada', $armada->id))
                ->orderByDesc('id')
                ->first();

            if (!$optimasi) {
                return redirect()->route('armada.dasbor')
                    ->with('error', 'Belum ada rute penjemputan untuk hari ini.');
            }

            // ✅ Ambil SEMUA tugas hari ini
            $semuaTugas = DetailRute::with(['permintaan.nasabah', 'permintaan.statusTerkini'])
                ->whereHas('optimasiRute', function ($q) {
                    $q->whereDate('tanggal', now()->toDateString());
                })
                ->where('id_armada', $armada->id)
                ->orderBy('urutan', 'asc')
                ->orderBy('id_optimasi', 'asc')                
                ->get();

            // ✅ Filter: hanya yang BELUM selesai/dibatalkan untuk peta
            $tugas = $semuaTugas->filter(function ($t) {
                $status = optional($t->permintaan->statusTerkini)->status;
                return !in_array($status, ['Selesai', 'Dibatalkan']);
            })->sortBy('urutan')->values();

            if ($tugas->isEmpty()) {
                return redirect()->route('armada.dasbor')
                    ->with('info', 'Semua tugas hari ini sudah selesai! 🎉');
            }

            $points = $this->formatPointsForMap($armada, $tugas);

            return view('frontend.armada.rute', compact('armada', 'tugas', 'optimasi', 'points'));

        } catch (\Exception $e) {
            Log::error('Error ArmadaController@rute: ' . $e->getMessage());
            return redirect()->route('armada.dasbor')->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Update status menjadi Selesai
     */
    public function selesai($idPermintaan)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $armada = Armada::where('email', $user->email)->first();

            if (!$armada) throw new \Exception('Data armada tidak ditemukan.');

            $permintaan = PermintaanPenjemputan::findOrFail($idPermintaan);

            $detailRute = DetailRute::where('id_permintaan', $idPermintaan)
                ->where('id_armada', $armada->id)
                ->first();

            if (!$detailRute) throw new \Exception('Permintaan bukan milik armada Anda.');

            $statusTerkini = $permintaan->statusTerkini;
            if ($statusTerkini && $statusTerkini->status === 'Selesai') {
                throw new \Exception('Permintaan ini sudah selesai.');
            }

            StatusPenjemputan::create([
                'permintaan_id'  => $idPermintaan,
                'status'         => 'Selesai',
                'keterangan'     => 'Penjemputan selesai oleh ' . $armada->nama_armada,
                'tanggal_status' => now()->toDateString(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Status penjemputan selesai.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error selesai: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function formatPointsForMap($armada, $tugas)
    {
        $points = [
            'depot' => [
                'lat' => (float) $armada->latitude_depot,
                'lng' => (float) $armada->longitude_depot,
            ],
            'tujuan' => [],
        ];

        foreach ($tugas as $item) {
            $points['tujuan'][] = [
                'lat'          => (float) $item->permintaan->latitude,
                'lng'          => (float) $item->permintaan->longitude,
                'nama'         => $item->permintaan->nasabah->nama_nasabah ?? 'Nasabah',
                'alamat'       => $item->permintaan->alamat_penjemputan,
                'urutan'       => $item->urutan,
                'berat'        => $item->permintaan->berat_sampah,
                'jenis_sampah' => $item->permintaan->jenis_sampah,
            ];
        }

        return $points;
    }
}