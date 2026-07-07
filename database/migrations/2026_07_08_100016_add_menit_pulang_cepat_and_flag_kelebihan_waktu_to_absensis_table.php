<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom untuk deteksi anomali jam pulang (pulang cepat / kelebihan
     * waktu tanpa lembur approved yang mencakupinya). Dihitung otomatis di
     * PresensiController::absenKeluar(), tapi tetap bisa dikoreksi manual oleh
     * admin lewat fitur edit absensi yang sudah ada (PetugasController::updateAbsensi()).
     */
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            if (!Schema::hasColumn('absensis', 'menit_pulang_cepat')) {
                $table->integer('menit_pulang_cepat')->default(0)->after('menit_telat');
            }
            if (!Schema::hasColumn('absensis', 'flag_kelebihan_waktu')) {
                $table->boolean('flag_kelebihan_waktu')->default(false)->after('menit_pulang_cepat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $columns = array_filter(
                ['menit_pulang_cepat', 'flag_kelebihan_waktu'],
                fn ($column) => Schema::hasColumn('absensis', $column)
            );

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
