<?php

namespace Database\Seeders;

use App\Models\PayrollSetting;
use Illuminate\Database\Seeder;

class PayrollSettingSeeder extends Seeder
{
    /**
     * Seed 1 baris pengaturan payroll default (single row). Idempoten:
     * tidak menambah baris kedua kalau sudah ada.
     */
    public function run(): void
    {
        if (PayrollSetting::exists()) {
            return;
        }

        PayrollSetting::create([
            'tanggal_gajian'                => 25,
            'rate_lembur_per_jam'           => 20000,
            'rate_potongan_telat_per_menit' => 1000,
            'toleransi_telat_menit'         => 10,
            'kuota_izin_sakit_per_bulan'    => 2,
        ]);
    }
}
