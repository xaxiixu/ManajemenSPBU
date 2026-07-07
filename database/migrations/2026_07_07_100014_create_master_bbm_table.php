<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_bbm', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_bbm', 50)->unique();
            $table->string('ron', 10)->nullable();
            $table->unsignedBigInteger('harga_per_liter');
            $table->foreignId('coa_pendapatan_id')->constrained('coa')->restrictOnDelete();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_bbm');
    }
};
