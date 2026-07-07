<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baseline: tabel `pengeluarans` sudah ada di database produksi (dibuat
     * manual - lihat CLAUDE.md). Migration ini hanya membuat tabel dari nol
     * untuk environment yang belum punya sama sekali; no-op terhadap
     * database produksi.
     */
    public function up(): void
    {
        if (Schema::hasTable('pengeluarans')) {
            return;
        }

        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('coa_id')->constrained('coa')->restrictOnDelete();
            $table->string('keterangan', 255);
            $table->unsignedBigInteger('jumlah');
            $table->string('bukti_pembayaran')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
