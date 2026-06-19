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
        Schema::create('detail_fisik_stok', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_rekon')->constrained('rekonsiliasi', 'id_rekon')->onDelete('cascade');
            $table->string('lokasi'); // Nama Lantai atau Plat Nomor
            $table->integer('jumlah_isi_fisik');
            $table->integer('jumlah_stok_fisik'); // Ini untuk jumlah tabung KOSONG fisik
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_fisik_stok');
    }
};
