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
        Schema::create('pangkalan_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pangkalan')->constrained('pangkalan', 'id_pangkalan');
            $table->integer('jumlah_terjual');
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pangkalan_usage');
    }
};
