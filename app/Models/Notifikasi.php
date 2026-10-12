<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $guarded = ['id'];

    public function statusPenjemputan()
    {
        return $this->belongsTo(StatusPenjemputan::class, 'status_penjemputan_id');
    }
}
