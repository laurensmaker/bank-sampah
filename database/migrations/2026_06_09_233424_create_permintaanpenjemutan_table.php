<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permintaanpenjemutan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nasabah')
                ->constrained('nasabah')
                ->cascadeOnDelete();
            $table->foreignId('id_optimasirute')->nullable()
                ->constrained('optimasirute')
                ->nullOnDelete();           
            $table->dateTime('tanggal');
            $table->string('alamat_penjemputan');
            $table->double('latitude', 10, 7);
            $table->double('longitude', 10, 7);
            $table->string('jenis_sampah');
            $table->float('berat_sampah', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaanpenjemutan');
    }
};
