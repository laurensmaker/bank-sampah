<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\Notifikasi;
use App\Models\PermintaanPenjemputan;
use App\Models\StatusPenjemputan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    public function markAsRead($id)
    {
        $notifikasi = Notifikasi::findOrFail($id);
        $notifikasi->update(['status_baca' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi telah dibaca',
            'data' => $notifikasi
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $notifikasiIds = collect([]);
        
        if ($user->role === 'nasabah') {
            $nasabah = Nasabah::where('email', $user->email)->first();
            
            if (!$nasabah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data nasabah tidak ditemukan'
                ]);
            }
            
            $permintaanIds = PermintaanPenjemputan::where('id_nasabah', $nasabah->id)->pluck('id');
            $notifikasiIds = StatusPenjemputan::whereIn('permintaan_id', $permintaanIds)->pluck('id');
        } elseif ($user->role === 'armada') {
            $armadaId = $user->id;
            
            $permintaanIds = PermintaanPenjemputan::where('id_optimasirute', $armadaId)->pluck('id');
            $notifikasiIds = StatusPenjemputan::whereIn('permintaan_id', $permintaanIds)->pluck('id');
        }
        
        $updated = Notifikasi::whereIn('status_penjemputan_id', $notifikasiIds)
                             ->where('status_baca', 'unread')
                             ->update(['status_baca' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi telah dibaca',
            'count' => $updated
        ]);
    }
}