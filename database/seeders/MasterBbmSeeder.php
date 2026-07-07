<?php

namespace Database\Seeders;

use App\Models\Coa;
use App\Models\MasterBbm;
use Illuminate\Database\Seeder;

class MasterBbmSeeder extends Seeder
{
    /**
     * Seed jenis BBM awal. coa_pendapatan_id dicari by kode_akun - kalau akun
     * COA-nya belum ada (mis. environment testing tanpa seed COA), barisnya
     * dilewati (perilaku silent-noop ini konsisten dengan JurnalService yang
     * juga tidak menganggap COA hilang sebagai error fatal).
     */
    public function run(): void
    {
        $data = [
            ['jenis_bbm' => 'Pertalite', 'ron' => '90', 'harga_per_liter' => 10000, 'kode_akun' => '4101-1'],
            ['jenis_bbm' => 'Pertamax',  'ron' => '92', 'harga_per_liter' => 12500, 'kode_akun' => '4101-2'],
            ['jenis_bbm' => 'Solar',     'ron' => null, 'harga_per_liter' => 6800,  'kode_akun' => '4101-3'],
        ];

        foreach ($data as $row) {
            $coaId = Coa::where('kode_akun', $row['kode_akun'])->value('id');

            if (!$coaId) {
                $this->command?->warn("MasterBbmSeeder: akun COA {$row['kode_akun']} tidak ditemukan, lewati {$row['jenis_bbm']}.");
                continue;
            }

            MasterBbm::updateOrCreate(
                ['jenis_bbm' => $row['jenis_bbm']],
                [
                    'ron'               => $row['ron'],
                    'harga_per_liter'   => $row['harga_per_liter'],
                    'coa_pendapatan_id' => $coaId,
                    'is_aktif'          => 1,
                ]
            );
        }
    }
}
