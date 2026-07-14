<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real case cuma ada 1 jenis jabatan petugas: operator. Nilai lama
     * (supervisor/kasir/teknisi/lainnya) dikonversi ke 'operator' sebelum
     * enum dipersempit.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('jabatan', '!=', 'operator')
            ->whereNotNull('jabatan')
            ->update(['jabatan' => 'operator']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('jabatan', ['operator'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('jabatan', ['operator', 'supervisor', 'kasir', 'teknisi', 'lainnya'])
                ->nullable()
                ->change();
        });
    }
};
