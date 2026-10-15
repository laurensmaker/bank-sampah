<?php

namespace Database\Seeders;

use App\Models\Armada;
use App\Models\Nasabah;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Yanto',
                'email' => 'yanto@gmail.com',
                'password' => bcrypt('yanto123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'Abet',
                'email' => 'abetrahman@gmail.com',
                'password' => bcrypt('abet123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'Agus',
                'email' => 'agus@gmail.com',
                'password' => bcrypt('agus123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'abdul',
                'email' => 'abdul@gmail.com',
                'password' => bcrypt('abdul123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'juan',
                'email' => 'juan@gmail.com',
                'password' => bcrypt('juan123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'korinus',
                'email' => 'korinus@gmail.com',
                'password' => bcrypt('korinus123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'kaleb',
                'email' => 'kaleb@gmail.com',
                'password' => bcrypt('kaleb123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'rian',
                'email' => 'rian@gmail.com',
                'password' => bcrypt('rian123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'alex',
                'email' => 'alex@gmail.com',
                'password' => bcrypt('alex123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'mas',
                'email' => 'mas@gmail.com',
                'password' => bcrypt('mas123'),
                'role' => 'nasabah',
            ],
            [
                'name' => 'markus',
                'email' => 'armada1@bank.com',
                'password' => bcrypt('armada123'),
                'role' => 'armada',
            ],
            [
                'name' => 'henry',
                'email' => 'armada2@bank.com',
                'password' => bcrypt('armada123'),
                'role' => 'armada',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // Buat data armada
        DB::table('armada')->insert([
            [
                'nama_armada' => 'Armada 1',
                'email' => 'armada1@bank.com',
                'jenis_kendaraan' => 'Pickup',
                'kapasitas' => 25,
                'status_armada' => 'Aktif',
                'latitude_depot' => -2.56104637197393,
                'longitude_depot' => 140.69282537798202,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_armada' => 'Armada 2',
                'email' => 'armada2@bank.com',
                'jenis_kendaraan' => 'Truck',
                'kapasitas' => 25,
                'status_armada' => 'Aktif',
                'latitude_depot' => -2.56104637197393,
                'longitude_depot' => 140.69282537798202,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $nasabah = [
            [
                'nama_nasabah' => 'Yanto Santoso',
                'alamat' => 'Kampus USTJ',
                'no_telepon' => '081234567890',
                'email' => 'yanto@gmail.com',
                'latitude' => -2.60497412465379,
                'longitude' => 140.65726237948763,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Abet',
                'alamat' => 'RS. Bayangkara',
                'no_telepon' => '082345678901',
                'email' => 'abetrahman@gmail.com',
                'latitude' => -2.5911072771808925,
                'longitude' => 140.67462017500415,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Agus',
                'alamat' => 'Jl. Saga Mall Abepura',
                'no_telepon' => '083456789012',
                'email' => 'agus@gmail.com',
                'latitude' => -2.6103058370952885,
                'longitude' => 140.6654510813376,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Abdul',
                'alamat' => 'Organda Dalam',
                'no_telepon' => '083456789013',
                'email' => 'abdul@gmail.com',
                'latitude' => -2.599194541424014,
                'longitude' => 140.65896582551605,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Juan',
                'alamat' => 'Jl. Kamkey',
                'no_telepon' => '083456789013',
                'email' => 'juan@gmail.com',
                'latitude' => -2.6134421438361577,
                'longitude' => 140.66876709483068,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Korinus',
                'alamat' => 'Jl. Perumnas 3',
                'no_telepon' => '083456789013',
                'email' => 'korinus@gmail.com',
                'latitude' => -2.6054275957079427,
                'longitude' => 140.65352699483043,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Kaleb',
                'alamat' => 'Jl. Perumnas1',
                'no_telepon' => '083456789013',
                'email' => 'kaleb@gmail.com',
                'latitude' => -2.596940109451197,
                'longitude' => 140.6371926393584,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Rian',
                'alamat' => 'Jl. padang bulan',
                'no_telepon' => '083456789013',
                'email' => 'rian@gmail.com',
                'latitude' => -2.5963220518376495,
                'longitude' => 140.65022370867052,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Alex',
                'alamat' => 'Jl. Organda',
                'no_telepon' => '083456789013',
                'email' => 'alex@gmail.com',
                'latitude' => -2.5963220518376495,
                'longitude' => 140.65022370867052,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nama_nasabah' => 'Mas',
                'alamat' => 'Jl. Organda',
                'no_telepon' => '083456789013',
                'email' => 'mas@gmail.com',
                'latitude' => -2.603163313146159,
                'longitude' => 140.66754865285043,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            

            
        ];

                // Insert data ke tabel nasabah

        foreach ($nasabah as $data) {
            Nasabah::create($data);
        }
    }

}
