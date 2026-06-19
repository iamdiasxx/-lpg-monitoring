<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Tabel SPBE
        Schema::create('spbe', function (Blueprint $table) {
            $table->id('id_spbe');
            $table->string('nama_spbe');
            $table->timestamps();
        });

        // 2. Tabel Pangkalan (Extended dari Users)
        Schema::create('pangkalan', function (Blueprint $table) {
            $table->id('id_pangkalan');
            $table->string('nik')->unique();
            $table->string('nama_pangkalan');
            $table->text('alamat');
            $table->string('no_hp');
            $table->string('no_registrasi');
            $table->string('status');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->integer('stok_saat_ini')->default(0);
            $table->timestamps();
        });

        // 3. Tabel Karyawan
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id('id_karyawan');
            $table->string('nama');
            $table->string('jabatan'); // Supir, Kernet, Gudang
            $table->string('no_hp');
            $table->timestamps();
        });

        // 4. Tabel Truk
        Schema::create('truk', function (Blueprint $table) {
            $table->id('id_truk');
            $table->string('plat_no');
            $table->string('tipe_truk');
            $table->string('kategori_kendaraan')->default('truk_besar');
            $table->timestamps();
        });


        // 5.1 Tabel Header Alokasi (Oleh Manager)
        Schema::create('header_alokasi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('id_spbe')->constrained('spbe', 'id_spbe');
            $table->integer('total_rencana_isi'); // Contoh: 1120 (2 DO)
            $table->integer('jumlah_truk'); // Contoh: 2
            $table->timestamps();
        });

        // 5.2 Tabel Alokasi (Oleh Manager)
        Schema::create('alokasi', function (Blueprint $table) {
            $table->id('id_alokasi');
            $table->foreignId('header_id')->nullable()->constrained('header_alokasi')->onDelete('cascade');
            $table->date('tanggal');
            $table->foreignId('id_spbe')->constrained('spbe', 'id_spbe');
            $table->foreignId('id_pangkalan')->constrained('pangkalan', 'id_pangkalan');
            $table->integer('jumlah_alokasi');
            $table->timestamps();
        });

        // 6. Tabel Stok Gudang (Pusat Saldo)
        Schema::create('stok_gudang', function (Blueprint $table) {
            $table->id('id_stok');
            $table->integer('jumlah_isi')->default(0);
            $table->integer('jumlah_kosong')->default(5906); // Start awal total aset
            $table->integer('lantai_1_isi')->default(0);
            $table->integer('lantai_1_kosong')->default(0);
            $table->integer('lantai_2_isi')->default(0);
            $table->integer('lantai_2_kosong')->default(0);
            $table->integer('lantai_3_isi')->default(0);
            $table->integer('lantai_3_kosong')->default(0);
            $table->timestamp('terakhir_update');
            $table->timestamps();
        });

        // 7. Tabel Transaksi SPBE (Validasi Penukaran Tabung)
        Schema::create('transaksi_spbe', function (Blueprint $table) {
            $table->id('id_trans_spbe');
            $table->foreignId('id_spbe')->constrained('spbe', 'id_spbe');
            $table->foreignId('id_truk')->constrained('truk', 'id_truk');
            $table->foreignId('id_karyawan')->constrained('karyawan', 'id_karyawan');
            $table->integer('jumlah_isi_masuk');
            $table->integer('jumlah_kosong_keluar');
            $table->timestamp('waktu_transaksi');
            $table->timestamps();
        });

        // 8. Tabel Distribusi Pangkalan (Eksekusi Pengiriman)
        Schema::create('distribusi_pangkalan', function (Blueprint $table) {
            $table->id('id_distribusi');
            $table->foreignId('id_alokasi')->constrained('alokasi', 'id_alokasi');
            $table->foreignId('id_pangkalan')->constrained('pangkalan', 'id_pangkalan');
            $table->foreignId('id_supir')->constrained('karyawan', 'id_karyawan');
            $table->foreignId('id_truk')->constrained('truk', 'id_truk');
            $table->integer('jumlah_isi_dikirim');
            $table->timestamp('waktu_berangkat');
            $table->string('status_penerimaan')->default('Proses'); // Proses, Diterima, Ditolak
            $table->timestamps();
        });

        // 9. Tabel Konfirmasi Pangkalan (Validasi Akhir)
        Schema::create('konfirmasi_pangkalan', function (Blueprint $table) {
            $table->id('id_konfirmasi');
            $table->foreignId('id_distribusi')->constrained('distribusi_pangkalan', 'id_distribusi');
            $table->string('status');
            $table->text('pesan_keterangan')->nullable();
            $table->string('foto_bukti')->nullable();
            $table->timestamp('waktu_konfirmasi');
            $table->timestamps();
        });

        // 10. Tabel Rekonsiliasi & Audit Trail
        Schema::create('rekonsiliasi', function (Blueprint $table) {
            $table->id('id_rekon');
            $table->date('tanggal');
            $table->foreignId('id_user_gudang')->constrained('users');
            $table->text('catatan_selisih');
            $table->timestamps();
        });

        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('id_user')->constrained('users');
            $table->string('aktivitas');
            $table->timestamp('waktu_log');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_trail');
        Schema::dropIfExists('rekonsiliasi');
        Schema::dropIfExists('konfirmasi_pangkalan');
        Schema::dropIfExists('distribusi_pangkalan');
        Schema::dropIfExists('transaksi_spbe');
        Schema::dropIfExists('stok_gudang');
        Schema::dropIfExists('alokasi');
        Schema::dropIfExists('truk');
        Schema::dropIfExists('karyawan');
        Schema::dropIfExists('pangkalan');
        Schema::dropIfExists('spbe');
    }
};