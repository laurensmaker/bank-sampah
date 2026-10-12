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
        Schema::create('detail_rute', function (Blueprint $table) {
            $table->id();
             $table->foreignId('id_optimasi')
                ->constrained('optimasirute')
                ->cascadeOnDelete();
            $table->foreignId('id_armada')
                ->constrained('armada')
                ->cascadeOnDelete();
            $table->foreignId('id_permintaan')
                ->constrained('permintaanpenjemutan')
                ->cascadeOnDelete();
            $table->unsignedInteger('urutan')->nullable();
            $table->double('jarak', 10, 2)->default(0);
            $table->double('total_jarak', 10, 2)->default(0);
            $table->unsignedInteger('estimasi_waktu')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_rute');
    }
};
