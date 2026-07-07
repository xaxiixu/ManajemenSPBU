<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengubah tabel `absensis` lama (kolom petugas_id, FK ke tabel petugas)
     * menjadi berbasis user_id (FK ke users), sesuai merge petugas -> users.
     * Data presensi lama dihapus (fresh start, sudah dikonfirmasi pemilik project).
     */
    public function up(): void
    {
        if (Schema::hasColumn('absensis', 'petugas_id')) {
            Schema::disableForeignKeyConstraints();

            // penjualan_bbm.absensis_id menunjuk ke baris absensis lama yang
            // akan dihapus - lepas dulu referensinya (data penjualan sendiri
            // tetap dipertahankan, cuma link ke sesi absensi lama diputus).
            if (Schema::hasTable('penjualan_bbm')) {
                DB::table('penjualan_bbm')->whereNotNull('absensis_id')->update(['absensis_id' => null]);
            }

            DB::table('absensis')->truncate();

            // Lepas FK yang masih menempel di petugas_id, kalau masih ada
            // (idempotent: retry sebelumnya mungkin sudah berhasil drop FK
            // ini duluan sebelum gagal di langkah lain).
            $fkAda = collect(DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensis'
                AND CONSTRAINT_NAME = 'absensis_petugas_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->isNotEmpty();

            if ($fkAda) {
                Schema::table('absensis', function (Blueprint $table) {
                    $table->dropForeign(['petugas_id']);
                });
            }

            // Lepas SEMUA index (termasuk unique composite yang tidak ikut
            // konvensi penamaan Laravel) yang masih melibatkan petugas_id,
            // supaya kolomnya bisa di-drop dengan aman.
            $indexNames = collect(DB::select("SHOW INDEX FROM absensis WHERE Column_name = 'petugas_id'"))
                ->pluck('Key_name')
                ->unique();

            foreach ($indexNames as $indexName) {
                DB::statement("ALTER TABLE absensis DROP INDEX `{$indexName}`");
            }

            Schema::table('absensis', function (Blueprint $table) {
                $table->dropColumn('petugas_id');
            });

            Schema::enableForeignKeyConstraints();
        }

        if (!Schema::hasColumn('absensis', 'user_id')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('absensis', 'menit_telat')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->integer('menit_telat')->default(0)->after('jam_keluar');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('absensis', 'menit_telat')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropColumn('menit_telat');
            });
        }

        if (Schema::hasColumn('absensis', 'user_id')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
