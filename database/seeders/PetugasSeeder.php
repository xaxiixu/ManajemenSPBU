<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PetugasSeeder extends Seeder
{
    /**
     * Seed akun petugas contoh untuk testing (login + presensi self-service).
     */
    public function run(): void
    {
        $petugas = [
            [
                'name'          => 'Budi Santoso',
                'email'         => 'budi.petugas@spbu.test',
                'nik'           => '3201010101010001',
                'jabatan'       => 'operator',
                'shift_default' => 'Pagi',
                'no_hp'         => '081200000001',
            ],
            [
                'name'          => 'Siti Aminah',
                'email'         => 'siti.petugas@spbu.test',
                'nik'           => '3201010101010002',
                'jabatan'       => 'operator',
                'shift_default' => 'Siang',
                'no_hp'         => '081200000002',
            ],
            [
                'name'          => 'Agus Wijaya',
                'email'         => 'agus.petugas@spbu.test',
                'nik'           => '3201010101010003',
                'jabatan'       => 'operator',
                'shift_default' => 'Malam',
                'no_hp'         => '081200000003',
            ],
        ];

        foreach ($petugas as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'          => $data['name'],
                    'password'      => Hash::make('password123'),
                    'role'          => 'petugas',
                    'is_active'     => 1,
                    'nik'           => $data['nik'],
                    'jabatan'       => $data['jabatan'],
                    'shift_default' => $data['shift_default'],
                    'no_hp'         => $data['no_hp'],
                ]
            );
        }
    }
}
