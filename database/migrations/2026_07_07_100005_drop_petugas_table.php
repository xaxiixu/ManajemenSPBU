<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel `petugas` di-merge ke `users` (role 'petugas'). Harus dijalankan
     * setelah FK absensis.petugas_id sudah dilepas (lihat migration sebelumnya).
     */
    public function up(): void
    {
        Schema::dropIfExists('petugas');
    }

    /**
     * Reverse the migrations.
     *
     * Catatan: down() ini hanya mengembalikan struktur tabel, bukan datanya
     * (data petugas lama sudah dihapus permanen saat up() berjalan).
     */
    public function down(): void
    {
        if (Schema::hasTable('petugas')) {
            return;
        }

        Schema::create('petugas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('nik', 20)->nullable()->unique();
            $table->enum('jabatan', ['operator', 'supervisor', 'kasir', 'teknisi', 'lainnya'])->default('operator');
            $table->enum('shift_default', ['Pagi', 'Siang', 'Malam'])->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }
};
