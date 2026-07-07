<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel riwayat yang FK user_id-nya diubah dari cascadeOnDelete ke
     * nullOnDelete, supaya penghapusan akun petugas tidak ikut menghapus
     * riwayat presensi/pengajuan shift/lembur secara permanen (lihat CLAUDE.md
     * & permintaan perbaikan Masalah 5). Kolom dijadikan nullable dulu karena
     * nullOnDelete mensyaratkan itu.
     */
    private array $tables = ['absensis', 'pengajuan_shift', 'lembur'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['user_id']);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('user_id')->nullable()->change();
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['user_id']);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('user_id')->nullable(false)->change();
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
