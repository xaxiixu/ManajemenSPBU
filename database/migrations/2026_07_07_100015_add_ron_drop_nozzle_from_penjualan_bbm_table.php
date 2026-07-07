<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revisi Master BBM: `penjualan_bbm` dapat kolom `ron` (snapshot RON saat
     * transaksi dibuat) dan kehilangan `nozzle` (tidak dipakai lagi). Guard
     * hasColumn dipakai karena baseline create_penjualan_bbm_table sudah
     * membuat bentuk final ini untuk instalasi baru - migration ini hanya
     * benar-benar mengubah struktur di database produksi yang masih bentuk
     * lama.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('penjualan_bbm', 'ron')) {
            Schema::table('penjualan_bbm', function (Blueprint $table) {
                $table->string('ron', 10)->nullable()->after('jenis_bbm');
            });
        }

        if (Schema::hasColumn('penjualan_bbm', 'nozzle')) {
            Schema::table('penjualan_bbm', function (Blueprint $table) {
                $table->dropColumn('nozzle');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('penjualan_bbm', 'nozzle')) {
            Schema::table('penjualan_bbm', function (Blueprint $table) {
                $table->string('nozzle', 10)->nullable()->after('pulau');
            });
        }

        if (Schema::hasColumn('penjualan_bbm', 'ron')) {
            Schema::table('penjualan_bbm', function (Blueprint $table) {
                $table->dropColumn('ron');
            });
        }
    }
};
