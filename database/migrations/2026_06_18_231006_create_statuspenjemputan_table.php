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
        Schema::create('statuspenjemputan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_id')
                    ->constrained('permintaanpenjemutan')
                    ->onDelete('cascade');
            $table->enum('status', ['Menunggu', 'Diproses', 'Dijadwalkan', 'Dijemput', 'Selesai', 'Dibatalkan'])->default('Menunggu');           
            $table->string('keterangan', 255)->nullable();
            $table->date('tanggal_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuspenjemputan');
    }
};
