<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menambah kolom di tabel pangkalan
        Schema::table('pangkalan', function (Blueprint $table) {
            $table->integer('stok_titipan')->default(0)->after('stok_saat_ini'); 
        });

        // Menambah kolom di tabel stok_gudang
        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->integer('jumlah_titipan')->default(0)->after('jumlah_kosong');
        });
    }

    public function down(): void
    {
        // Ini untuk membatalkan jika suatu saat kamu melakukan migrate:rollback
        Schema::table('pangkalan', function (Blueprint $table) {
            $table->dropColumn('stok_titipan');
        });

        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->dropColumn('jumlah_titipan');
        });
    }
};