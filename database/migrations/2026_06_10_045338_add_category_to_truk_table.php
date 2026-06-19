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
        // Kolom ini sudah ada di migration create_lpg_system_tables sejak
        // 2026-05-21; guard ini mencegah error "column already exists" saat
        // migrate dijalankan dari kosong (fresh database).
        if (Schema::hasColumn('truk', 'kategori_kendaraan')) {
            return;
        }

        Schema::table('truk', function (Blueprint $table) {
            $table->string('kategori_kendaraan')->default('truk_besar')->after('tipe_truk');
        });
    }

    public function down(): void
    {
        Schema::table('truk', function (Blueprint $table) {
            $table->dropColumn('kategori_kendaraan');
        });
    }
};
