<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailOptimasiRute extends Model
{
    protected $table = 'detail_rute';

    protected $fillable = [
        'id_optimasi',
        'id_armada',
        'id_permintaan',
        'urutan',
        'jarak',
        'total_jarak',
        'estimasi_waktu',
    ];

    public function optimasiRute()
    {
        return $this->belongsTo(OptimasiRute::class, 'id_optimasi');
    }

    public function armada()
    {
        return $this->belongsTo(Armada::class, 'id_armada');
    }

    public function permintaan()
    {
        return $this->belongsTo(PermintaanPenjemputan::class, 'id_permintaan');
    }
}
