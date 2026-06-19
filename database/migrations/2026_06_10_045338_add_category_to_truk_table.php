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
        Schema::table('truk', function (Blueprint $table) {
            // Tambahkan kategori: truk_besar atau mobil_colt
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
