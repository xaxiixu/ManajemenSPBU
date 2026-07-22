<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom baru: jumlah_tidak_tercatat — banyaknya HARI KERJA dalam periode
     * yang TIDAK PUNYA record absensi sama sekali untuk petugas ini (bukan
     * hadir, bukan sakit/izin, bukan alpha — benar-benar kosong).
     *
     * Sebelumnya hari kosong dianggap "libur" dan gratis tanpa efek. Sekarang
     * setiap hari kosong diberi potongan setara alpha SEBAGAI DEFAULT DRAFT
     * (lihat PayrollService::hitung), supaya kelalaian pencatatan pengawas tidak
     * membuat petugas yang sebenarnya bolos tetap tergaji penuh tanpa terdeteksi.
     *
     * Nilai ini bersifat informatif/peringatan: manager wajib memverifikasi &
     * boleh memperbaiki dengan mencatat status kehadiran yang benar lalu
     * meng-generate ulang draft.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payroll_details')) {
            return;
        }

        if (Schema::hasColumn('payroll_details', 'jumlah_tidak_tercatat')) {
            return;
        }

        Schema::table('payroll_details', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_tidak_tercatat')->default(0)->after('jumlah_izin');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_details', 'jumlah_tidak_tercatat')) {
            Schema::table('payroll_details', function (Blueprint $table) {
                $table->dropColumn('jumlah_tidak_tercatat');
            });
        }
    }
};
