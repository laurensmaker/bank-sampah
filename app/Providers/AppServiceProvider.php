<?php

namespace App\Providers;

use App\Models\Armada;
use App\Models\Nasabah;
use App\Models\Notifikasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (!Auth::check()) {
                $view->with([
                    'notifications' => collect([]),
                    'unreadCount' => 0,
                    'readCount' => 0
                ]);
                return;
            }

            $user = Auth::user();
            $notifications = collect([]);
            $unreadCount = 0;
            $readCount = 0;

            if ($user->role === 'nasabah') {
                $nasabah = Nasabah::where('email', $user->email)->first();

                if ($nasabah) {
                    $notifications = Notifikasi::select('notifikasi.*')
                        ->join('statuspenjemputan', 'notifikasi.status_penjemputan_id', '=', 'statuspenjemputan.id')
                        ->join('permintaanpenjemutan', 'statuspenjemputan.permintaan_id', '=', 'permintaanpenjemutan.id')
                        ->where('permintaanpenjemutan.id_nasabah', $nasabah->id)
                        ->orderBy('notifikasi.created_at', 'desc')
                        ->limit(10)
                        ->get();

                    $unreadCount = Notifikasi::select('notifikasi.*')
                        ->join('statuspenjemputan', 'notifikasi.status_penjemputan_id', '=', 'statuspenjemputan.id')
                        ->join('permintaanpenjemutan', 'statuspenjemputan.permintaan_id', '=', 'permintaanpenjemutan.id')
                        ->where('permintaanpenjemutan.id_nasabah', $nasabah->id)
                        ->where('notifikasi.status_baca', 'unread')
                        ->count();

                    $readCount = Notifikasi::select('notifikasi.*')
                        ->join('statuspenjemputan', 'notifikasi.status_penjemputan_id', '=', 'statuspenjemputan.id')
                        ->join('permintaanpenjemutan', 'statuspenjemputan.permintaan_id', '=', 'permintaanpenjemutan.id')
                        ->where('permintaanpenjemutan.id_nasabah', $nasabah->id)
                        ->where('notifikasi.status_baca', 'read')
                        ->orderBy('notifikasi.created_at', 'desc')
                        ->count();
                }
            } elseif ($user->role === 'armada') {
                $armadaId = $user->id;

                $notifications = Notifikasi::select('notifikasi.*')
                    ->join('statuspenjemputan', 'notifikasi.status_penjemputan_id', '=', 'statuspenjemputan.id')
                    ->join('permintaanpenjemutan', 'statuspenjemputan.permintaan_id', '=', 'permintaanpenjemutan.id')
                    ->where('permintaanpenjemutan.id_optimasirute', $armadaId)
                    ->orderBy('notifikasi.created_at', 'desc')
                    ->limit(10)
                    ->get();

                $unreadCount = Notifikasi::select('notifikasi.*')
                    ->join('statuspenjemputan', 'notifikasi.status_penjemputan_id', '=', 'statuspenjemputan.id')
                    ->join('permintaanpenjemutan', 'statuspenjemputan.permintaan_id', '=', 'permintaanpenjemutan.id')
                    ->where('permintaanpenjemutan.id_optimasirute', $armadaId)
                    ->where('notifikasi.status_baca', 'unread')
                    ->count();

                $readCount = Notifikasi::select('notifikasi.*')
                    ->join('statuspenjemputan', 'notifikasi.status_penjemputan_id', '=', 'statuspenjemputan.id')
                    ->join('permintaanpenjemutan', 'statuspenjemputan.permintaan_id', '=', 'permintaanpenjemutan.id')
                    ->where('permintaanpenjemutan.id_optimasirute', $armadaId)
                    ->where('notifikasi.status_baca', 'read')
                    ->orderBy('notifikasi.created_at', 'desc')
                    ->count();
            }

            $view->with([
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'readCount' => $readCount
            ]);
        });
    }
}
