<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Armada extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'armada';

    protected $fillable = [
        'nama_armada',
        'email',
        'password',
        'latitude_depot',
        'longitude_depot',
        'kapasitas',
        'status_armada',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'kapasitas' => 'decimal:2',
        'password'  => 'hashed',
    ];

    /**
     * ✅ Armada punya banyak detail rute
     */
    public function detailRute()
    {
        return $this->hasMany(DetailRute::class, 'id_armada');
    }

    /**
     * ✅ Optimasi yang dibuat armada ini (sebagai prioritas)
     */
    public function optimasiSebagaiPrioritas()
    {
        return $this->hasMany(OptimasiRute::class, 'id_armada_prioritas');
    }

    /**
     * ✅ Optimasi yang melibatkan armada ini (via detailrute)
     */
    public function optimasiTerlibat()
    {
        return $this->hasManyThrough(
            OptimasiRute::class,
            DetailRute::class,
            'id_armada',
            'id',
            'id',
            'id_optimasi'
        )->distinct();
    }

    private function formatPointsForMap($armada, $tugas)
    {
        $points = [
            'depot' => [
                'lat' => (float) $armada->latitude_depot,
                'lng' => (float) $armada->longitude_depot,
            ],
            'tujuan' => [],
        ];

        // ✅ Pastikan $tugas sudah orderBy('urutan', 'asc')
        //    agar index juga sesuai urutan
        foreach ($tugas as $item) {
            $points['tujuan'][] = [
                    'lat'          => (float) $item->permintaan->latitude,
                    'lng'          => (float) $item->permintaan->longitude,
                    'nama'         => $item->permintaan->nasabah->nama_nasabah ?? 'Nasabah',
                    'alamat'       => $item->permintaan->alamat_penjemputan,
                    'urutan'       => (int) ($item->urutan ?? 0),   // ✅ cast ke int
                    'berat'        => (float) $item->permintaan->berat_sampah,
                    'jenis_sampah' => $item->permintaan->jenis_sampah,
                ];
            }

            return $points;
    }
}