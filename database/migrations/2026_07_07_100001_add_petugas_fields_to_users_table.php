<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catatan: kolom `role` & `is_active` sendiri TIDAK ada di stock migration
     * create_users_table (lihat CLAUDE.md) - keduanya sudah ada di database
     * produksi (dibuat manual). Migration ini membuatnya dari nol kalau belum
     * ada (mis. sqlite testing / instalasi baru), baru kemudian memperluas
     * enum role untuk menambahkan 'petugas'.
     */
    public function up(): void
    {
        $roleSudahAda = Schema::hasColumn('users', 'role');

        Schema::table('users', function (Blueprint $table) use ($roleSudahAda) {
            if (!$roleSudahAda) {
                $table->enum('role', ['it', 'manager', 'pengawas', 'petugas'])
                    ->default('pengawas')
                    ->after('password');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik', 20)->nullable()->unique()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'jabatan')) {
                $table->enum('jabatan', ['operator', 'supervisor', 'kasir', 'teknisi', 'lainnya'])->nullable()->after('nik');
            }
            if (!Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('jabatan');
            }
            if (!Schema::hasColumn('users', 'shift_default')) {
                $table->enum('shift_default', ['Pagi', 'Siang', 'Malam'])->nullable()->after('no_hp');
            }
        });

        // Kalau kolom role sudah ada sebelumnya (database produksi, enum lama
        // cuma 3 nilai), perluas jadi 4 nilai termasuk 'petugas'.
        if ($roleSudahAda) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['it', 'manager', 'pengawas', 'petugas'])
                    ->default('pengawas')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['manager', 'pengawas', 'it'])
                    ->default('pengawas')
                    ->change();
            });
        }

        if (Schema::hasColumn('users', 'nik')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['nik']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(
                ['nik', 'jabatan', 'no_hp', 'shift_default'],
                fn ($column) => Schema::hasColumn('users', $column)
            );

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
