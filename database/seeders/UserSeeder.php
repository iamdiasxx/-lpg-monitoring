<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Admin
        User::create([
            'name' => 'Admin LPG',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        // 2. Buat Operator
        User::create([
            'name' => 'Operator Gudang',
            'email' => 'operator@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'operator'
        ]);

        // 3. Buat 76 Customer secara otomatis
        for ($i = 1; $i <= 76; $i++) {
            User::create([
                'name' => 'Customer Ke-' . $i,
                'email' => 'customer' . $i . '@gmail.com', // customer1@gmail.com, customer2@gmail.com, dst.
                'password' => Hash::make('password'), // Semua password disamakan agar mudah testing
                'role' => 'customer'
            ]);
        }


    }
}