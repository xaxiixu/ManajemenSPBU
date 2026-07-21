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
                'name'              => 'Budi Santoso',
                'email'             => 'budi.petugas@spbu.test',
                'nik'               => '3201010101010001',
                'jabatan'           => 'operator',
                'shift_default'     => 'Pagi',
                'no_hp'             => '081200000001',
                'gaji_pokok'        => 3000000,
                'tanggal_bergabung' => '2024-01-15', // lama (gaji penuh)
            ],
            [
                'name'              => 'Siti Aminah',
                'email'             => 'siti.petugas@spbu.test',
                'nik'               => '3201010101010002',
                'jabatan'           => 'operator',
                'shift_default'     => 'Siang',
                'no_hp'             => '081200000002',
                'gaji_pokok'        => 2800000,
                'tanggal_bergabung' => '2024-06-01', // lama (gaji penuh)
            ],
            [
                'name'              => 'Agus Wijaya',
                'email'             => 'agus.petugas@spbu.test',
                'nik'               => '3201010101010003',
                'jabatan'           => 'operator',
                'shift_default'     => 'Malam',
                'no_hp'             => '081200000003',
                'gaji_pokok'        => 3200000,
                'tanggal_bergabung' => '2026-07-10', // baru (uji prorate periode 26 Jun–25 Jul)
            ],
        ];

        foreach ($petugas as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password123'),
                    'role'              => 'petugas',
                    'is_active'         => 1,
                    'nik'               => $data['nik'],
                    'jabatan'           => $data['jabatan'],
                    'shift_default'     => $data['shift_default'],
                    'no_hp'             => $data['no_hp'],
                    'gaji_pokok'        => $data['gaji_pokok'],
                    'tanggal_bergabung' => $data['tanggal_bergabung'],
                ]
            );
        }
    }
}
