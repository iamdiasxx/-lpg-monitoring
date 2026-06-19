<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom-kolom ini sudah ada di migration create_lpg_system_tables sejak
        // 2026-05-21; guard ini mencegah error "column already exists" saat
        // migrate dijalankan dari kosong (fresh database).
        if (Schema::hasColumn('stok_gudang', 'lantai_1_isi')) {
            return;
        }

        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->integer('lantai_1_isi')->default(0)->after('jumlah_kosong');
            $table->integer('lantai_1_kosong')->default(0)->after('lantai_1_isi');

            $table->integer('lantai_2_isi')->default(0)->after('lantai_1_kosong');
            $table->integer('lantai_2_kosong')->default(0)->after('lantai_2_isi');

            $table->integer('lantai_3_isi')->default(0)->after('lantai_2_kosong');
            $table->integer('lantai_3_kosong')->default(0)->after('lantai_3_isi');
        });
    }

    public function down(): void
    {
        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->dropColumn([
                'lantai_1_isi', 'lantai_1_kosong', 
                'lantai_2_isi', 'lantai_2_kosong', 
                'lantai_3_isi', 'lantai_3_kosong'
            ]);
        });
    }
};