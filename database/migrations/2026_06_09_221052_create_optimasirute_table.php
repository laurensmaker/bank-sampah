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
        Schema::create('optimasirute', function (Blueprint $table) {
            $table->id();
             $table->date('tanggal');
            $table->string('metode_pembagian')->default('MTSP');
            $table->string('metode_rute')->default('Nearest Neighbor');
            $table->unsignedInteger('jumlah_permintaan')->default(0);
            $table->unsignedInteger('jumlah_armada')->default(0);
            $table->decimal('total_berat', 8, 2)->default(0);
            $table->decimal('total_jarak', 10, 2)->default(0);
            $table->unsignedInteger('total_waktu')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optimasirute');
    }
};
