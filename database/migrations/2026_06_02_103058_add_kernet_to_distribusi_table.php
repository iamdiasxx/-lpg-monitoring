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
        Schema::table('distribusi_pangkalan', function (Blueprint $table) {
            // Tambahkan id_kernet setelah id_supir
            $table->foreignId('id_kernet')->nullable()->after('id_supir')->constrained('karyawan', 'id_karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribusi_pangkalan', function (Blueprint $table) {
            //
        });
    }
};
