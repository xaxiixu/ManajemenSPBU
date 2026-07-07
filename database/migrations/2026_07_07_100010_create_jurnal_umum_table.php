<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baseline: tabel `jurnal_umum` sudah ada di database produksi (dibuat
     * manual - lihat CLAUDE.md). Migration ini hanya membuat tabel dari nol
     * untuk environment yang belum punya sama sekali; no-op terhadap
     * database produksi.
     */
    public function up(): void
    {
        if (Schema::hasTable('jurnal_umum')) {
            return;
        }

        Schema::create('jurnal_umum', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_jurnal', 30)->unique();
            $table->date('tanggal');
            $table->string('keterangan', 255)->nullable();
            // Sumber transaksi ('penjualan_bbm' / 'pengeluaran') + referensi_id:
            // referensi generik ke baris sumbernya, bukan FK sungguhan karena
            // menunjuk ke tabel berbeda tergantung nilai `sumber`.
            $table->string('sumber', 30);
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_umum');
    }
};
