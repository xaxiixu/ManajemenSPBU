<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Baseline: tabel `absensis` sudah ada di database produksi (dibuat manual,
     * bukan lewat migration - lihat CLAUDE.md). Migration ini hanya membuat
     * tabel dari nol untuk environment yang belum punya sama sekali (mis. sqlite
     * testing / instalasi baru), langsung dalam bentuk final (user_id based).
     * Terhadap database produksi yang sudah ada tabel lama (petugas_id based),
     * migration ini no-op dan transformasinya dilakukan oleh migration berikutnya.
     */
    public function up(): void
    {
        if (Schema::hasTable('absensis')) {
            return;
        }

        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('shift', ['Pagi', 'Siang', 'Malam']);
            $table->enum('status_hadir', ['hadir', 'sakit', 'izin', 'tidak_hadir'])->default('hadir');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->integer('menit_telat')->default(0);
            $table->string('keterangan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
