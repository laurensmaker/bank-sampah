<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptimasiRute extends Model
{
    protected $table = 'optimasirute';

    protected $fillable = [
        'tanggal',
        'metode_pembagian',
        'metode_rute',
        'jumlah_permintaan',
        'jumlah_armada',
        'total_berat',
        'total_jarak',
        'total_waktu',
    ];

    public function detailRute()
    {
        return $this->hasMany(DetailRute::class, 'id_optimasi');
    }

    public function armada()
    {
        return $this->hasMany(Armada::class, 'id_optimasirute');
    }
}
