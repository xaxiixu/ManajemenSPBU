<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ManagerSeeder extends Seeder
{
    /**
     * Seed satu akun manager contoh untuk testing (satu-satunya role yang bisa
     * mengakses Penggajian, Jurnal Umum, Buku Besar, & Laporan Laba Rugi).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'manager@spbu.test'],
            [
                'name' => 'Andi Manager',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'is_active' => 1,
            ]
        );
    }
}
