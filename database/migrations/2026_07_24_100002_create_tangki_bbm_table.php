<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel baru (belum ada di produksi sama sekali - beda dari coa/jurnal_umum/
     * dst yang baseline-nya sudah dibuat manual). Guard hasTable dipakai murni
     * untuk idempotensi, mengikuti pola tabel payroll_* (2026_07_22_*), bukan
     * pola "baseline produksi manual" seperti coa.
     *
     * stok_liter & kapasitas_liter integer (liter bulat), konsisten dengan
     * kolom liter lain di project (penjualan_bbm.liter_terjual dst - tidak ada
     * pecahan liter di manapun di codebase ini).
     *
     * harga_pokok_rata2 disimpan sebagai rupiah bulat (unsignedBigInteger,
     * bukan decimal) - konsisten dengan seluruh nominal uang lain di project
     * (harga_per_liter, total_penjualan, jumlah di jurnal_detail semua bulat).
     * Hasil moving average di-round ke rupiah terdekat saat dihitung (lihat
     * TangkiBbmService pada Fase 3), bukan disimpan sebagai pecahan.
     */
    public function up(): void
    {
        if (Schema::hasTable('tangki_bbm')) {
            return;
        }

        Schema::create('tangki_bbm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_bbm_id')->constrained('master_bbm')->restrictOnDelete();
            $table->string('nama_tangki', 100);
            $table->unsignedBigInteger('kapasitas_liter');
            $table->unsignedBigInteger('stok_liter')->default(0);
            $table->unsignedBigInteger('harga_pokok_rata2')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tangki_bbm');
    }
};
