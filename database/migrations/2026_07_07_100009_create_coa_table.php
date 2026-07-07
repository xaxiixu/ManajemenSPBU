<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baseline: tabel `coa` sudah ada di database produksi (dibuat manual,
     * bukan lewat migration - lihat CLAUDE.md). Migration ini hanya membuat
     * tabel dari nol untuk environment yang belum punya sama sekali (mis.
     * sqlite testing / instalasi baru); no-op terhadap database produksi.
     */
    public function up(): void
    {
        if (Schema::hasTable('coa')) {
            return;
        }

        Schema::create('coa', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun', 20)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('coa')->nullOnDelete();
            $table->string('nama_akun', 100);
            $table->enum('kategori', ['aset', 'kewajiban', 'modal', 'pendapatan', 'beban']);
            $table->enum('posisi_normal', ['debit', 'kredit']);
            $table->string('deskripsi', 255)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coa');
    }
};
