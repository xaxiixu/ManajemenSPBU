<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baseline: tabel `penjualan_bbm` sudah ada di database produksi (dibuat
     * manual - lihat CLAUDE.md). Migration ini membuat tabel dari nol untuk
     * environment yang belum punya sama sekali, langsung dalam bentuk FINAL
     * (kolom `ron`, tanpa `nozzle`) sesuai revisi Master BBM. Terhadap
     * database produksi yang masih berbentuk lama (ada `nozzle`, belum ada
     * `ron`), migration ini no-op dan transformasinya dilakukan oleh
     * migration berikutnya (add_ron_drop_nozzle_from_penjualan_bbm_table).
     */
    public function up(): void
    {
        if (Schema::hasTable('penjualan_bbm')) {
            return;
        }

        Schema::create('penjualan_bbm', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('shift', ['Pagi', 'Siang', 'Malam']);
            $table->foreignId('absensis_id')->nullable()->constrained('absensis')->nullOnDelete();
            $table->string('pulau', 10);
            $table->string('jenis_bbm', 50);
            $table->string('ron', 10)->nullable();
            $table->foreignId('coa_pendapatan_id')->constrained('coa')->restrictOnDelete();
            $table->unsignedBigInteger('meter_awal');
            $table->unsignedBigInteger('meter_akhir');
            $table->unsignedBigInteger('liter_terjual');
            $table->unsignedBigInteger('harga_per_liter');
            $table->unsignedBigInteger('total_penjualan');
            $table->string('foto_meter_awal')->nullable();
            $table->string('foto_meter_akhir')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_bbm');
    }
};
