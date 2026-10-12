<?php

// routes/web.php
use App\Http\Controllers\ArmadaController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NasabahController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\OptimasiController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    return view('frontend.beranda');
});

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.submit');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {

    // ─── NASABAH ROUTES ──────────────────────────────────────
    Route::prefix('nasabah')->name('nasabah.')->group(function () {
        Route::get('/', [NasabahController::class, 'dasbor'])->name('dasbor');
        Route::get('/permintaan-penjemputan', [NasabahController::class, 'permintaanPenjemputan'])->name('permintaan-penjemputan');
        Route::post('/permintaan-penjemputan', [NasabahController::class, 'storePermintaanPenjemputan'])->name('permintaan-penjemputan.store');
        Route::get('/riwayat-penjemputan', [NasabahController::class, 'riwayatPenjemputan'])->name('riwayat-penjemputan');
        Route::delete('/permintaan/{id}/batalkan', [NasabahController::class, 'batalkanPermintaan'])->name('permintaan.batalkan');
    });

    // ─── ARMADA ROUTES ───────────────────────────────────────
    Route::prefix('armada')->name('armada.')->group(function () {
        Route::get('/dasbor', [ArmadaController::class, 'index'])->name('dasbor');
        Route::get('/rute', [ArmadaController::class, 'rute'])->name('rute');
        Route::post('/selesai/{idPermintaan}', [ArmadaController::class, 'selesai'])->name('selesai');
        
        // ✅ ROUTE UNTUK OPTIMASI
        Route::get('/optimasi-proses', [ArmadaController::class, 'optimasiProses'])->name('optimasi.proses');
        Route::post('/optimasi', [ArmadaController::class, 'optimasiRute'])->name('optimasi');
    });

    // ─── OPTIMASI ROUTES ─────────────────────────────────────
    Route::prefix('optimasi')->name('optimasi.')->group(function () {
        Route::get('/', [OptimasiController::class, 'index'])->name('index');
        Route::post('/proses', [OptimasiController::class, 'proses'])->name('proses');
        Route::get('/show/{id}', [OptimasiController::class, 'show'])->name('show');
        Route::get('/history', [OptimasiController::class, 'history'])->name('history');
    });

    // ─── NOTIFIKASI ROUTES ──────────────────────────────────
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::post('/{id}/baca', [NotifikasiController::class, 'markAsRead'])->name('baca');
        Route::post('/baca-semua', [NotifikasiController::class, 'markAllAsRead'])->name('baca.semua');
    });

});