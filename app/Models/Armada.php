<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Armada extends Model
{
     protected $table = 'armada';

    protected $fillable = [
        'id_optimasirute',
        'nama_armada',
        'email',
        'jenis_kendaraan',
        'kapasitas',
        'status_armada',
        'latitude_depot',
        'longitude_depot',
    ];

    public function optimasiRute()
    {
        return $this->belongsTo(OptimasiRute::class, 'id_optimasirute');
    }

    public function detailRute()
    {
        return $this->hasMany(DetailRute::class, 'id_armada');
    }
}
