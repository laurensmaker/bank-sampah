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
        Schema::create('armada', function (Blueprint $table) {
           $table->id();
            $table->string('nama_armada');
            $table->string('email')->unique();
            $table->string('jenis_kendaraan');
            $table->integer('kapasitas');
            $table->string('status_armada');
            $table->double('latitude_depot', 10, 7)->default(0);
            $table->double('longitude_depot', 10, 7)->default(0);
            $table->timestamps();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('armada');
    }
};
