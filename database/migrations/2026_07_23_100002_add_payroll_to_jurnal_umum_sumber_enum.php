<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan nilai 'payroll' ke kolom `sumber` di tabel jurnal_umum,
     * supaya jurnal otomatis dari penggajian bisa dibedakan dari penjualan_bbm
     * & pengeluaran.
     *
     * Driver-aware:
     *  - MySQL/MariaDB: kalau kolom `sumber` bertipe ENUM (kondisi produksi yang
     *    dibuat manual), lakukan MODIFY untuk menambah 'payroll' sambil
     *    mempertahankan daftar nilai lama & nullability-nya. Kalau ternyata sudah
     *    VARCHAR biasa, tidak ada yang perlu diubah (VARCHAR menerima nilai apa pun).
     *  - sqlite (dipakai test) & driver lain: kolom `sumber` hanyalah string tanpa
     *    constraint enum, jadi migration ini no-op — 'payroll' otomatis diterima.
     */
    public function up(): void
    {
        if (! Schema::hasTable('jurnal_umum')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return; // sqlite/postgres/dll: kolom string, tidak ada enum untuk diubah
        }

        $col = DB::selectOne(
            "SHOW COLUMNS FROM `jurnal_umum` WHERE Field = 'sumber'"
        );

        if (! $col || stripos($col->Type, 'enum') !== 0) {
            return; // bukan enum (sudah varchar) → tidak perlu diubah
        }

        // Ambil daftar nilai enum yang ada sekarang: enum('penjualan_bbm','pengeluaran')
        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $col->Type, $m);
        $nilai = $m[1] ?? [];

        if (! in_array('payroll', $nilai, true)) {
            $nilai[] = 'payroll';
        }

        $daftar   = collect($nilai)->map(fn ($v) => "'" . str_replace("'", "''", $v) . "'")->implode(',');
        $nullable = strtoupper($col->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default  = $col->Default !== null ? " DEFAULT '" . str_replace("'", "''", $col->Default) . "'" : '';

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

        // Kembalikan tanpa 'payroll' (hanya kalau tidak ada baris yang memakainya,
        // supaya tidak merusak data). Kalau masih ada jurnal 'payroll', biarkan.
        $masihDipakai = DB::table('jurnal_umum')->where('sumber', 'payroll')->exists();
        if ($masihDipakai) {
            return;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $col->Type, $m);
        $nilai = collect($m[1] ?? [])->reject(fn ($v) => $v === 'payroll')->values();

        if ($nilai->isEmpty()) {
            return;
        }

        $daftar   = $nilai->map(fn ($v) => "'" . str_replace("'", "''", $v) . "'")->implode(',');
        $nullable = strtoupper($col->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default  = $col->Default !== null ? " DEFAULT '" . str_replace("'", "''", $col->Default) . "'" : '';

        DB::statement("ALTER TABLE `jurnal_umum` MODIFY `sumber` ENUM($daftar) {$nullable}{$default}");
    }
};
