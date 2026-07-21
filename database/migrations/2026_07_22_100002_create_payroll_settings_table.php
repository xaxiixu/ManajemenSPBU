<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan global payroll - single row (satu SPBU, satu konfigurasi).
     * Seed 1 baris default lewat PayrollSettingSeeder.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_settings')) {
            return;
        }

        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            // Tanggal gajian tiap bulan (1-31). Kalau > jumlah hari bulan
            // tertentu, di-clamp ke hari terakhir bulan itu (logic di service).
            $table->unsignedTinyInteger('tanggal_gajian')->default(25);
            $table->unsignedBigInteger('rate_lembur_per_jam')->default(20000);
            $table->unsignedBigInteger('rate_potongan_telat_per_menit')->default(1000);
            $table->unsignedInteger('toleransi_telat_menit')->default(10);
            $table->unsignedInteger('kuota_izin_sakit_per_bulan')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
