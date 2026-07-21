<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyesuaian manual (bonus / potongan) per payroll_detail. Bisa banyak
     * baris per detail. jumlah signed: positif = bonus, negatif = potongan.
     *
     * Hanya bisa ditambah/edit/hapus selama payroll_run induk masih 'draft'
     * (dicek di controller). Setelah run dikirim, ikut terkunci.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_penyesuaian')) {
            return;
        }

        Schema::create('payroll_penyesuaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_detail_id')->constrained('payroll_details')->cascadeOnDelete();
            $table->string('keterangan', 255);
            $table->bigInteger('jumlah'); // signed: + bonus / - potongan
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_penyesuaian');
    }
};
