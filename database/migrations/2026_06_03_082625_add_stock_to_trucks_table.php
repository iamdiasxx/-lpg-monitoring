<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('truk', function (Blueprint $table) {
            $table->integer('stok_isi')->default(0)->after('tipe_truk');
            $table->integer('stok_kosong')->default(0)->after('stok_isi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('truk', function (Blueprint $table) {
            //
        });
    }
};
