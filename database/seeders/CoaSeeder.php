<?php

namespace Database\Seeders;

use App\Models\Coa;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    /**
     * Baseline akun COA minimal yang dibutuhkan JurnalService & MasterBbmSeeder
     * agar jurnal otomatis (penjualan BBM, pengeluaran, payroll, pembelian BBM)
     * bisa berjalan di environment yang belum punya data COA (mis. sqlite baru /
     * instalasi lokal). firstOrCreate by kode_akun — aman dijalankan berkali-kali
     * dan tidak menimpa akun yang sudah ada (mis. di database produksi yang
     * dibuat manual, lihat CLAUDE.md). Di produksi, akun 1101/1104/1104-x/4101-x/
     * 5103 sudah ada secara manual — baris ini hanya no-op untuknya; satu-satunya
     * akun yang benar-benar baru di produksi adalah 5104 (HPP).
     *
     * Urutan penting: parent (1104) harus ada sebelum child-nya (1104-1/2/3)
     * di-resolve lewat parent_kode_akun.
     */
    public function run(): void
    {
        $akun = [
            ['kode_akun' => '1101', 'nama_akun' => 'Kas', 'kategori' => 'aset', 'posisi_normal' => 'debit'],
            ['kode_akun' => '1104', 'nama_akun' => 'Persediaan BBM', 'kategori' => 'aset', 'posisi_normal' => 'debit'],
            ['kode_akun' => '1104-1', 'nama_akun' => 'Persediaan Pertalite', 'kategori' => 'aset', 'posisi_normal' => 'debit', 'parent_kode_akun' => '1104'],
            ['kode_akun' => '1104-2', 'nama_akun' => 'Persediaan Pertamax', 'kategori' => 'aset', 'posisi_normal' => 'debit', 'parent_kode_akun' => '1104'],
            ['kode_akun' => '1104-3', 'nama_akun' => 'Persediaan Solar', 'kategori' => 'aset', 'posisi_normal' => 'debit', 'parent_kode_akun' => '1104'],
            ['kode_akun' => '4101-1', 'nama_akun' => 'Pendapatan Penjualan Pertalite', 'kategori' => 'pendapatan', 'posisi_normal' => 'kredit'],
            ['kode_akun' => '4101-2', 'nama_akun' => 'Pendapatan Penjualan Pertamax', 'kategori' => 'pendapatan', 'posisi_normal' => 'kredit'],
            ['kode_akun' => '4101-3', 'nama_akun' => 'Pendapatan Penjualan Solar', 'kategori' => 'pendapatan', 'posisi_normal' => 'kredit'],
            ['kode_akun' => '5103', 'nama_akun' => 'Beban Gaji Karyawan', 'kategori' => 'beban', 'posisi_normal' => 'debit'],
            ['kode_akun' => '5104', 'nama_akun' => 'Harga Pokok Penjualan (HPP)', 'kategori' => 'beban', 'posisi_normal' => 'debit'],
        ];

        foreach ($akun as $row) {
            $parentId = isset($row['parent_kode_akun'])
                ? Coa::where('kode_akun', $row['parent_kode_akun'])->value('id')
                : null;

            Coa::firstOrCreate(
                ['kode_akun' => $row['kode_akun']],
                [
                    'parent_id' => $parentId,
                    'nama_akun' => $row['nama_akun'],
                    'kategori' => $row['kategori'],
                    'posisi_normal' => $row['posisi_normal'],
                    'is_aktif' => 1,
                ]
            );
        }
    }
}
