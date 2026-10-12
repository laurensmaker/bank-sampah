<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\Nasabah;
use App\Models\Notifikasi;
use App\Models\OptimaSirute;
use App\Models\PermintaanPenjemputan;
use App\Models\StatusPenjemputan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NasabahController extends Controller
{
    public function dasbor()
    {
        $user = Auth::user();
        $nasabah = Nasabah::where('email', $user->email)->first();
        
        $notifications = collect([]);
        $unreadCount = 0;
        $readCount = 0;
        
        if ($nasabah) {
            $permintaanIds = PermintaanPenjemputan::where('id_nasabah', $nasabah->id)->pluck('id');
            $statusIds = StatusPenjemputan::whereIn('permintaan_id', $permintaanIds)->pluck('id');
            
            $notifications = Notifikasi::whereIn('status_penjemputan_id', $statusIds)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $unreadCount = $notifications->where('status_baca', 'unread')->count();
            $readCount = $notifications->where('status_baca', 'read')->count();
        }

        return view('frontend.nasabah.dasbor', compact('notifications', 'unreadCount', 'readCount'));
    }

    public function permintaanPenjemputan()
    {
        $nasabah = Nasabah::where('email', auth()->user()->email)->first();
        return view('frontend.nasabah.permintaan-penjemputan', compact('nasabah'));
    }

      public function riwayatPenjemputan()
    {
        $user = Auth::user();
        
        // Cari nasabah berdasarkan email user
        $nasabah = \App\Models\Nasabah::where('email', $user->email)->first();
        
        if (!$nasabah) {
            return redirect()->back()->with('error', 'Data nasabah tidak ditemukan.');
        }

        // Ambil semua permintaan dengan relasi status terkini
        $permintaan = PermintaanPenjemputan::with(['statusTerkini', 'nasabah'])
            ->where('id_nasabah', $nasabah->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('frontend.nasabah.riwayat-penjemputan', [
            'nasabah' => $permintaan
        ]);
    }

    public function batalkanPermintaan($id)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $nasabah = \App\Models\Nasabah::where('email', $user->email)->first();
            
            if (!$nasabah) {
                throw new \Exception('Data nasabah tidak ditemukan.');
            }

            // Cari permintaan milik nasabah ini
            $permintaan = PermintaanPenjemputan::where('id', $id)
                ->where('id_nasabah', $nasabah->id)
                ->first();

            if (!$permintaan) {
                throw new \Exception('Permintaan tidak ditemukan.');
            }

            // Cek status terkini
            $statusTerkini = $permintaan->statusTerkini;
            
            if (!$statusTerkini || $statusTerkini->status !== 'Menunggu') {
                throw new \Exception('Permintaan tidak dapat dibatalkan karena sedang diproses.');
            }

            // Tambahkan status baru: Dibatalkan
            StatusPenjemputan::create([
                'permintaan_id' => $id,
                'status' => 'Dibatalkan',
                'keterangan' => 'Dibatalkan oleh nasabah',
                'tanggal_status' => now()->toDateString(),
            ]);

            // Update id_optimasirute menjadi null jika ada
            $permintaan->update(['id_optimasirute' => null]);

            DB::commit();

            return redirect()->route('nasabah.riwayat-penjemputan')
                ->with('success', 'Permintaan penjemputan berhasil dibatalkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error batalkan permintaan: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal membatalkan permintaan: ' . $e->getMessage());
        }
    }

    public function storePermintaanPenjemputan(Request $request)
    {
        
        $data = $request->validate([
            'tanggal' => 'required|date',
            'alamat_penjemputan' => 'required|string',
            // 'latitude' => 'required|numeric',
            // 'longitude' => 'required|numeric',
            'jenis_sampah' => 'required|string',
            'berat_sampah' => 'required|numeric',
        ]);

        $nasabah = Nasabah::where('email', Auth::user()->email)->first();

        $idNasabah = $request->session()->get('id_nasabah');
        if (! $idNasabah) {
            return back()->withErrors(['nasabah' => 'Akun belum terdaftar sebagai nasabah.']);
        }
        
        $permintaan = PermintaanPenjemputan::create([
            'id_nasabah' => $nasabah->id,
            'id_optimasirute' => null,
            'id_statuspenjemputan' => 1, // 1 = Menunggu
            'tanggal' => $request->tanggal,
            'alamat_penjemputan' => $request->alamat_penjemputan,
            'latitude' => $nasabah->latitude, // Ambil dari tabel nasabah
            'longitude' => $nasabah->longitude, // Ambil dari tabel nasabah
            'jenis_sampah' => $request->jenis_sampah,
            'berat_sampah' => $request->berat_sampah,
            'catatan' => $request->catatan, // Jika ada field catatan
        ]);

        StatusPenjemputan::create([
            'permintaan_id' => $permintaan->id,
            'status' => 'Menunggu',
            'keterangan' => 'Permintaan penjemputan telah dibuat dan menunggu konfirmasi armada.',
            'tanggal_status' => now(),
        ]);
        
        $status = StatusPenjemputan::where('permintaan_id', $permintaan->id)->first();
        Notifikasi::create([
            'status_penjemputan_id' => $status->id,
            'isi_notifikasi' => 'Permintaan penjemputan sedang diproses petugas.',
            'tanggal_notifikasi' => now(),
            'status_baca' => 'unread',
        ]);

        // Jalankan optimasi rute secara otomatis
        // $this->jalankanOptimasiRute();

        return redirect()->route('nasabah.riwayat-penjemputan')->with('success', 'Permintaan penjemputan berhasil dibuat.');
    }
    
  
    
    
}
