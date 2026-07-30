<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan nilai 'saldo_awal_akun' ke kolom `sumber` di tabel
     * jurnal_umum - dipakai jurnal dari fitur Saldo Awal per-akun COA
     * (JurnalService::dariSaldoAwalAkun()). SENGAJA dibuat sebagai nilai
     * enum TERPISAH dari 'saldo_awal' (fitur Saldo Awal Kas+Persediaan yang
     * sudah ada) supaya proteksi one-time-lock milik halaman itu tidak ikut
     * memblokir fitur ini - akun COA baru boleh diberi saldo awal berkali-
     * kali untuk akun yang berbeda-beda. Pola identik dengan migration
     * 2026_07_26_100001 (penambahan 'saldo_awal') - lihat migration itu
     * untuk penjelasan driver-aware lengkap.
     */
    public function up(): void
    {
        if (! Schema::hasTable('jurnal_umum')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $col = DB::selectOne(
            "SHOW COLUMNS FROM `jurnal_umum` WHERE Field = 'sumber'"
        );

        if (! $col || stripos($col->Type, 'enum') !== 0) {
            return;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $col->Type, $m);
        $nilai = $m[1] ?? [];

        if (! in_array('saldo_awal_akun', $nilai, true)) {
            $nilai[] = 'saldo_awal_akun';
        }

        $daftar   = collect($nilai)->map(fn ($v) => "'".str_replace("'", "''", $v)."'")->implode(',');
        $nullable = strtoupper($col->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default  = $col->Default !== null ? " DEFAULT '".str_replace("'", "''", $col->Default)."'" : '';

        DB::statement("ALTER TABLE `jurnal_umum` MODIFY `sumber` ENUM($daftar) {$nullable}{$default}");
    }

    public function down(): void
    {
        if (! Schema::hasTable('jurnal_umum') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $col = DB::selectOne(
            "SHOW COLUMNS FROM `jurnal_umum` WHERE Field = 'sumber'"
        );

        if (! $col || stripos($col->Type, 'enum') !== 0) {
            return;
        }

        $masihDipakai = DB::table('jurnal_umum')->where('sumber', 'saldo_awal_akun')->exists();
        if ($masihDipakai) {
            return;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $col->Type, $m);
        $nilai = collect($m[1] ?? [])->reject(fn ($v) => $v === 'saldo_awal_akun')->values();

        if ($nilai->isEmpty()) {
            return;
        }

        $daftar   = $nilai->map(fn ($v) => "'".str_replace("'", "''", $v)."'")->implode(',');
        $nullable = strtoupper($col->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default  = $col->Default !== null ? " DEFAULT '".str_replace("'", "''", $col->Default)."'" : '';

        DB::statement("ALTER TABLE `jurnal_umum` MODIFY `sumber` ENUM($daftar) {$nullable}{$default}");
    }
};
