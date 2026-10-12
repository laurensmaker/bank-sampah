<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPenjemputan extends Model
{
    use HasFactory;

    protected $table = 'statuspenjemputan';

    protected $fillable = [
        'permintaan_id',
        'status',
        'keterangan',
        'tanggal_status',
    ];

    protected $casts = [
        'tanggal_status' => 'date',
    ];

    // Relasi ke permintaan
    public function permintaan()
    {
        return $this->belongsTo(PermintaanPenjemputan::class, 'permintaan_id');
    }

    // Scope untuk status tertentu
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeTerbaru($query)
    {
        return $query->latest('tanggal_status')->latest('created_at');
    }
}