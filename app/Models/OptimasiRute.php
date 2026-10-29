<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimasiRute extends Model
{
    use HasFactory;

    // ✅ Nama tabel eksplisit
    protected $table = 'optimasirute';

    protected $fillable = [
        'tanggal',
        'id_armada_prioritas',
        'metode_pembagian',
        'metode_rute',
        'jumlah_permintaan',
        'jumlah_armada',
        'total_berat',
        'total_jarak',
        'total_waktu',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'total_berat' => 'decimal:2',
        'total_jarak' => 'decimal:2',
        'total_waktu' => 'integer',
    ];

    /**
     * ✅ Relasi ke armada prioritas (yang membuat optimasi)
     */
    public function armadaPrioritas()
    {
        return $this->belongsTo(Armada::class, 'id_armada_prioritas');
    }

    /**
     * ✅ Relasi ke detail rute
     */
    public function detailRute()
    {
        return $this->hasMany(DetailRute::class, 'id_optimasi');
    }

    /**
     * ✅ Relasi ke semua armada yang terlibat (via detailrute)
     */
    public function armadaTerlibat()
    {
        return $this->hasManyThrough(
            Armada::class,
            DetailRute::class,
            'id_optimasi',    // FK di detailrute
            'id',             // FK di armada
            'id',             // PK di optimasirute
            'id_armada'       // FK di detailrute yang nunjuk ke armada
        )->distinct();
    }
}