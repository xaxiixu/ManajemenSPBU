<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baseline: tabel `jurnal_detail` sudah ada di database produksi (dibuat
     * manual - lihat CLAUDE.md). Migration ini hanya membuat tabel dari nol
     * untuk environment yang belum punya sama sekali; no-op terhadap
     * database produksi.
     */
    public function up(): void
    {
        if (Schema::hasTable('jurnal_detail')) {
            return;
        }

        Schema::create('jurnal_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_id')->constrained('jurnal_umum')->cascadeOnDelete();
            $table->foreignId('coa_id')->constrained('coa')->restrictOnDelete();
            $table->enum('posisi', ['debit', 'kredit']);
            $table->unsignedBigInteger('jumlah');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_detail');
    }
};
