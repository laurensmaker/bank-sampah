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
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
             $table->foreignId('status_penjemputan_id')
                ->constrained('statuspenjemputan')
                ->cascadeOnDelete();
            $table->string('isi_notifikasi', 100);
            $table->date('tanggal_notifikasi');
            $table->enum('status_baca', ['read', 'unread'])->default('unread');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
