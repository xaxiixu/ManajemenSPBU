<?php

namespace Database\Seeders;

use App\Models\Coa;
use App\Models\MasterBbm;
use Illuminate\Database\Seeder;

class MasterBbmCoaPersediaanSeeder extends Seeder
{
    /**
     * Backfill kolom coa_persediaan_id di master_bbm (mis. Pertalite -> 1104-1).
     *
     * SENGAJA seeder terpisah dari MasterBbmSeeder: MasterBbmSeeder melakukan
     * updateOrCreate yang meng-overwrite harga_per_liter/ron ke nilai seed
     * setiap dijalankan ulang - tidak aman dijalankan lagi di produksi karena
     * harga sudah diedit manual sejak seed awal (mis. Pertamax kini 16000,
     * bukan 12500). Seeder ini HANYA meng-update satu kolom (coa_persediaan_id)
     * dan HANYA kalau kolomnya masih kosong, jadi aman dijalankan berkali-kali
     * tanpa risiko menimpa data harga yang sudah berubah.
     */
    public function run(): void
    {
        $map = [
            'Pertalite' => '1104-1',
            'Pertamax'  => '1104-2',
            'Solar'     => '1104-3',
        ];

        foreach ($map as $jenisBbm => $kodeAkun) {
            $coaId = Coa::where('kode_akun', $kodeAkun)->value('id');

            if (! $coaId) {
                $this->command?->warn("MasterBbmCoaPersediaanSeeder: akun COA {$kodeAkun} tidak ditemukan, lewati {$jenisBbm}.");
                continue;
            }

            MasterBbm::where('jenis_bbm', $jenisBbm)
                ->whereNull('coa_persediaan_id')
                ->update(['coa_persediaan_id' => $coaId]);
        }
    }
}
