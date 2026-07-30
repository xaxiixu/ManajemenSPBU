<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom baru: saldo_awal — nilai saldo saat akun COA ini DIBUAT (statis,
     * historis, tidak dihitung ulang). Diisi opsional lewat form Tambah Akun
     * COA untuk kategori aset/kewajiban/modal (lihat CoaController::store()).
     * Null berarti tidak diisi (termasuk semua akun kategori
     * pendapatan/beban, dan akun-akun lama yang dibuat sebelum kolom ini
     * ada) - dibedakan dari 0 supaya "-" di tabel index tetap bermakna.
     */
    public function up(): void
    {
        if (! Schema::hasTable('coa')) {
            return;
        }

        if (Schema::hasColumn('coa', 'saldo_awal')) {
            return;
        }

        Schema::table('coa', function (Blueprint $table) {
            $table->decimal('saldo_awal', 15, 2)->nullable()->default(null)->after('deskripsi');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('coa', 'saldo_awal')) {
            Schema::table('coa', function (Blueprint $table) {
                $table->dropColumn('saldo_awal');
            });
        }
    }
};
