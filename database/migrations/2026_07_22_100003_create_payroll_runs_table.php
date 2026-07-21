<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header payroll per periode. status:
     *  - draft  : masih bisa di-regenerate/hapus/edit penyesuaian
     *  - dikirim: final, terkunci (slip terbit ke petugas)
     *
     * Unique (periode_mulai, periode_selesai, status) mencegah dua run dengan
     * status sama untuk periode yang sama persis (mis. dua 'dikirim' = double
     * payroll). Draft dan dikirim untuk periode sama tidak akan berbarengan
     * karena saat "kirim" baris draft-nya sendiri yang di-flip jadi dikirim,
     * dan regenerate menghapus draft lama sebelum bikin baru.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_runs')) {
            return;
        }

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->enum('status', ['draft', 'dikirim'])->default('draft');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamps();

            $table->unique(['periode_mulai', 'periode_selesai', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
