<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role 'it' dihapus - tidak ada lagi departemen IT terpisah, akses IT
     * (manajemen user) dipindahkan ke role 'manager'. User existing dengan
     * role 'it' dikonversi ke 'manager' sebelum enum dipersempit.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'it')->update(['role' => 'manager']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['manager', 'pengawas', 'petugas'])
                ->default('pengawas')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['it', 'manager', 'pengawas', 'petugas'])
                ->default('pengawas')
                ->change();
        });
    }
};
