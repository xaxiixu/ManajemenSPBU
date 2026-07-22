<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautan master_bbm -> akun COA Persediaan (mis. Pertalite -> 1104-1),
     * mengikuti pola coa_pendapatan_id yang sudah ada. Dipakai modul
     * persediaan/pembelian/HPP untuk resolusi akun tanpa hardcode ID.
     *
     * Nullable (bukan wajib seperti coa_pendapatan_id) karena kolom ini
     * ditambahkan ke tabel master_bbm yang sudah berisi data produksi -
     * nilainya di-backfill terpisah lewat seeder, bukan lewat migration ini.
     */
    public function up(): void
    {
        if (! Schema::hasTable('master_bbm')) {
            return;
        }

        if (Schema::hasColumn('master_bbm', 'coa_persediaan_id')) {
            return;
        }

        Schema::table('master_bbm', function (Blueprint $table) {
            $table->foreignId('coa_persediaan_id')->nullable()->after('coa_pendapatan_id')
                ->constrained('coa')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('master_bbm', 'coa_persediaan_id')) {
            Schema::table('master_bbm', function (Blueprint $table) {
                $table->dropConstrainedForeignId('coa_persediaan_id');
            });
        }
    }
};
