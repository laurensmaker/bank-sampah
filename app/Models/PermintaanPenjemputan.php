<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanPenjemputan extends Model
{
    use HasFactory;

    protected $table = 'permintaanpenjemutan';

    protected $fillable = [
        'id_nasabah',
        'id_optimasirute',
        'tanggal',
        'alamat_penjemputan',
        'latitude',
        'longitude',
        'jenis_sampah',
        'berat_sampah',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'berat_sampah' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // Relasi ke nasabah
    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class, 'id_nasabah');
    }

    // Relasi ke optimasi rute
    public function optimasiRute()
    {
        return $this->belongsTo(OptimasiRute::class, 'id_optimasirute');
    }

    // Relasi ke semua status penjemputan
    public function statusPenjemputan()
    {
        return $this->hasMany(StatusPenjemputan::class, 'permintaan_id');
    }

    // Relasi ke status terkini (terbaru) - INI YANG PALING PENTING
    public function statusTerkini()
    {
        return $this->hasOne(StatusPenjemputan::class, 'permintaan_id')
            ->latest('tanggal_status')
            ->latest('created_at');
    }

    // Relasi ke detail rute
    public function detailRute()
    {
        return $this->hasOne(DetailRute::class, 'id_permintaan');
    }

    // Scope untuk filter status
    public function scopeStatusMenunggu($query)
    {
        return $query->whereHas('statusTerkini', function ($q) {
            $q->where('status', 'Menunggu');
        });
    }

    public function scopeStatusDiproses($query)
    {
        return $query->whereHas('statusTerkini', function ($q) {
            $q->where('status', 'Diproses');
        });
    }

    public function scopeStatusDijadwalkan($query)
    {
        return $query->whereHas('statusTerkini', function ($q) {
            $q->where('status', 'Dijadwalkan');
        });
    }

    public function scopeStatusSelesai($query)
    {
        return $query->whereHas('statusTerkini', function ($q) {
            $q->where('status', 'Selesai');
        });
    }
}