<?php

namespace Database\Seeders;

use App\Models\MasterBbm;
use App\Models\TangkiBbm;
use Illuminate\Database\Seeder;

class TangkiBbmSeeder extends Seeder
{
    /**
     * Satu tangki awal per jenis BBM aktif di master_bbm. firstOrCreate by
     * master_bbm_id - aman dijalankan berkali-kali, tidak membuat tangki
     * duplikat kalau sudah ada.
     *
     * stok_liter & harga_pokok_rata2 SENGAJA mulai dari 0, bukan diisi angka
     * dummy seolah-olah itu stok/harga pokok nyata: modul ini baru mulai
     * melacak persediaan dari titik ini, jadi lebih jujur & aman membiarkan
     * tangki "kosong" dan biarkan stok + harga_pokok_rata2 terisi murni dari
     * transaksi pembelian BBM sungguhan (Fase 3) - daripada menebak angka
     * yang bisa keliru dan mendistorsi HPP transaksi pertama. Kalau manager
     * punya data stok fisik riil saat rollout, sebaiknya diinput manual
     * lewat 1 baris "pembelian penyesuaian awal" setelah Fase 3 jalan,
     * supaya tetap tercatat rapi di jurnal & buku besar.
     *
     * kapasitas_liter diisi angka dummy wajar (10.000 liter/tangki) sebagai
     * placeholder kapasitas fisik - silakan disesuaikan manual per tangki
     * sesuai kapasitas riil.
     */
    public function run(): void
    {
        $kapasitasDummy = 10000;

        MasterBbm::where('is_aktif', 1)->get()->each(function (MasterBbm $masterBbm) use ($kapasitasDummy) {
            TangkiBbm::firstOrCreate(
                ['master_bbm_id' => $masterBbm->id],
                [
                    'nama_tangki'       => 'Tangki '.$masterBbm->jenis_bbm,
                    'kapasitas_liter'   => $kapasitasDummy,
                    'stok_liter'        => 0,
                    'harga_pokok_rata2' => 0,
                    'is_aktif'          => 1,
                ]
            );
        });
    }
}
