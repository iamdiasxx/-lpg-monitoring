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
        Schema::table('transaksi_spbe', function (Blueprint $table) {
            // Tambahkan header_id untuk mengunci transaksi ke alokasi tertentu
            $table->foreignId('header_id')->nullable()->after('id_trans_spbe')->constrained('header_alokasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_spbe', function (Blueprint $table) {
            //
        });
    }
};
