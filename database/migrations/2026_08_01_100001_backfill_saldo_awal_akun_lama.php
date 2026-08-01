<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill kolom coa.saldo_awal untuk 5 akun (1101 Kas, 1104-1/2/3
     * Persediaan BBM, 3101 Modal Pemilik) yang saldo awalnya dulu diinput
     * lewat halaman Saldo Awal terpisah (mekanisme lama, sumber
     * 'saldo_awal', SATU jurnal gabungan JRN-20260426-0001) - SEBELUM kolom
     * coa.saldo_awal dibuat (migration add_saldo_awal_to_coa_table). Kolom
     * itu jadi tetap NULL untuk kelima akun ini walau nilainya sudah ada di
     * jurnal_detail, bikin form Edit COA (yang query jurnal_detail
     * langsung) dan tabel index COA (yang baca coa.saldo_awal apa adanya)
     * menampilkan nilai berbeda untuk akun yang sama.
     *
     * Nilai diambil dari jurnal_detail milik jurnal sumber='saldo_awal',
     * di-join balik ke posisi_normal akun masing-masing (aset -> baris
     * debit, modal -> baris kredit) - bukan angka hardcode. Murni isi
     * kolom tampilan, TIDAK membuat/mengubah baris jurnal apa pun.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('coa', 'saldo_awal')) {
            return;
        }

        $rows = DB::table('jurnal_detail')
            ->join('jurnal_umum', 'jurnal_umum.id', '=', 'jurnal_detail.jurnal_id')
            ->join('coa', 'coa.id', '=', 'jurnal_detail.coa_id')
            ->where('jurnal_umum.sumber', 'saldo_awal')
            ->whereIn('coa.kode_akun', ['1101', '1104-1', '1104-2', '1104-3', '3101'])
            ->whereColumn('jurnal_detail.posisi', 'coa.posisi_normal')
            ->whereNull('coa.saldo_awal')
            ->select('coa.id as coa_id', 'jurnal_detail.jumlah')
            ->get();

        foreach ($rows as $row) {
            DB::table('coa')->where('id', $row->coa_id)->update(['saldo_awal' => $row->jumlah]);
        }
    }

    public function down(): void
    {
        DB::table('coa')
            ->whereIn('kode_akun', ['1101', '1104-1', '1104-2', '1104-3', '3101'])
            ->update(['saldo_awal' => null]);
    }
};
