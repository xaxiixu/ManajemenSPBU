<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautan penjualan_bbm -> tangki_bbm + snapshot HPP saat transaksi terjadi.
     * Kolom ditambahkan ke tabel yang sudah berisi data produksi lama, jadi
     * KEDUANYA nullable: data lama tidak akan pernah punya nilai ini (tidak
     * di-backfill secara retroaktif - HPP transaksi lama tidak diketahui),
     * dan PenjualanBbm::boot() sendiri membiarkannya null kalau jenis BBM
     * belum punya tangki terdaftar (lihat model).
     */
    public function up(): void
    {
        if (! Schema::hasTable('penjualan_bbm')) {
            return;
        }

        if (Schema::hasColumn('penjualan_bbm', 'tangki_id')) {
            return;
        }

        Schema::table('penjualan_bbm', function (Blueprint $table) {
            $table->foreignId('tangki_id')->nullable()->after('jenis_bbm')
                ->constrained('tangki_bbm')->nullOnDelete();
            $table->unsignedBigInteger('hpp_per_liter')->nullable()->after('harga_per_liter');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('penjualan_bbm', 'hpp_per_liter')) {
            Schema::table('penjualan_bbm', function (Blueprint $table) {
                $table->dropColumn('hpp_per_liter');
            });
        }

        if (Schema::hasColumn('penjualan_bbm', 'tangki_id')) {
            Schema::table('penjualan_bbm', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tangki_id');
            });
        }
    }
};
