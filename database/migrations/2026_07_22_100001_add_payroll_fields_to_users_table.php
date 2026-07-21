<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom payroll di tabel users - HANYA relevan untuk role 'petugas'
     * (pengawas/manager digaji di luar sistem). Keduanya nullable supaya
     * user non-petugas / petugas lama yang belum di-set tidak error.
     *
     * - gaji_pokok       : nominal bulanan tetap (rupiah)
     * - tanggal_bergabung: acuan prorate gaji petugas baru
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gaji_pokok')) {
                $table->unsignedBigInteger('gaji_pokok')->nullable()->after('shift_default');
            }
            if (!Schema::hasColumn('users', 'tanggal_bergabung')) {
                $table->date('tanggal_bergabung')->nullable()->after('gaji_pokok');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(
                ['gaji_pokok', 'tanggal_bergabung'],
                fn ($column) => Schema::hasColumn('users', $column)
            );

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
