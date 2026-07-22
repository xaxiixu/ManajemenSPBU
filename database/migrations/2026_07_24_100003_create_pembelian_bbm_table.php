<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel baru (belum ada di produksi) - guard hasTable murni untuk
     * idempotensi, mengikuti pola tabel payroll_x / tangki_bbm, bukan pola
     * "baseline produksi manual" seperti coa.
     *
     * volume_liter, harga_per_liter, subtotal semuanya bulat (unsignedBigInteger/
     * unsignedInteger), konsisten dengan kolom sejenis di penjualan_bbm.
     * subtotal dihitung otomatis di model event (lihat PembelianBbm::boot()),
     * bukan dipercaya dari input.
     */
    public function up(): void
    {
        if (Schema::hasTable('pembelian_bbm')) {
            return;
        }

        Schema::create('pembelian_bbm', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('nama_supplier', 150);
            $table->foreignId('tangki_id')->constrained('tangki_bbm')->restrictOnDelete();
            $table->unsignedBigInteger('volume_liter');
            $table->unsignedInteger('harga_per_liter');
            $table->unsignedBigInteger('subtotal');
            $table->enum('status_bayar', ['tunai', 'kredit']);
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_bbm');
    }
};
