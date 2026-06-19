<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void {
        // 1. Users
        $admin = \App\Models\User::create([
            'name' => 'Manager Area',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $gudang = \App\Models\User::create([
            'name' => 'Petugas Gudang 1',
            'email' => 'operator@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'operator'
        ]);

        // 2. SPBE
        $spbe = \DB::table('spbe')->insertGetId(['nama_spbe' => 'SPBE Pertamina Patra Niaga', 'created_at' => now()]);

        // 3. Truk & Karyawan
        $truk = \DB::table('truk')->insertGetId(['plat_no' => 'B 9001 LPG', 'tipe_truk' => 'Double Colt Diesel']);
        $supir = \DB::table('karyawan')->insertGetId(['nama' => 'Budi Supir', 'jabatan' => 'Supir', 'no_hp' => '08123']);

        // 4. Stok Awal Gudang (PENTING: Total 5906 Aset)
        \DB::table('stok_gudang')->insert([
            'jumlah_isi' => 0,
            'jumlah_kosong' => 5906,
            'terakhir_update' => now()
        ]);

        // 5. Buat 76 Pangkalan secara otomatis
        for ($i = 1; $i <= 76; $i++) {
            $u = \App\Models\User::create([
                'name' => 'Pangkalan ' . $i,
                'email' => 'pangkalan' . $i . '@gmail.com',
                'password' => bcrypt('password'),
                'role' => 'customer'
            ]);

            \DB::table('pangkalan')->insert([
                'nama_pangkalan' => $u->name,
                'nik' => '3201000' . $i,
                'alamat' => 'Alamat Pangkalan Ke-' . $i,
                'no_hp' => '08999' . $i,
                'no_registrasi' => 'REG-' . $i,
                'status' => 'Aktif',
                'id_user' => $u->id,
                'stok_saat_ini' => 0,
                'created_at' => now()
            ]);
        }
    }
}
