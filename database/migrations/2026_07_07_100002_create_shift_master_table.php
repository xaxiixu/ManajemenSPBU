<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('shift_master')) {
            return;
        }

        Schema::create('shift_master', function (Blueprint $table) {
            $table->id();
            $table->enum('shift', ['Pagi', 'Siang', 'Malam'])->unique();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();
        });

        DB::table('shift_master')->insert([
            ['shift' => 'Pagi',  'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['shift' => 'Siang', 'jam_mulai' => '15:00:00', 'jam_selesai' => '23:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['shift' => 'Malam', 'jam_mulai' => '23:00:00', 'jam_selesai' => '07:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_master');
    }
};
