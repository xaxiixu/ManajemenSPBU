<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian gaji per petugas per payroll_run. Semua angka hasil hitung
     * PayrollService di-snapshot di sini saat generate, supaya histori tidak
     * berubah walau data absensi/setting/gaji_pokok berubah setelahnya.
     *
     * Catatan kolom int bertanda (integer, bukan unsignedBigInteger) untuk
     * nominal yang secara logika selalu >= 0 tetap dipakai bigInteger biasa
     * agar aman; hanya total_penyesuaian yang boleh negatif.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_details')) {
            return;
        }

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Gaji pokok setelah prorate (kalau petugas baru bergabung di tengah
            // periode). Untuk petugas lama = gaji_pokok penuh.
            $table->unsignedBigInteger('gaji_pokok_prorate')->default(0);

            // Rekap kehadiran (informatif + basis potongan)
            $table->unsignedInteger('jumlah_hadir')->default(0);
            $table->unsignedInteger('jumlah_kali_telat')->default(0);
            $table->unsignedInteger('total_menit_telat_kena_potong')->default(0);
            $table->unsignedBigInteger('potongan_telat')->default(0);

            $table->unsignedInteger('jumlah_alpha')->default(0);
            $table->unsignedInteger('jumlah_sakit')->default(0);
            $table->unsignedInteger('jumlah_izin')->default(0);
            $table->unsignedBigInteger('potongan_absen')->default(0);

            $table->unsignedInteger('jumlah_jam_lembur')->default(0);
            $table->unsignedBigInteger('uang_lembur')->default(0);

            // Total penyesuaian manual (bonus positif / potongan negatif).
            // Signed karena boleh negatif.
            $table->bigInteger('total_penyesuaian')->default(0);

            // Gaji bersih akhir (di-floor ke 0, tidak pernah negatif).
            $table->unsignedBigInteger('total_gaji_bersih')->default(0);

            $table->timestamps();

            $table->index(['payroll_run_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
