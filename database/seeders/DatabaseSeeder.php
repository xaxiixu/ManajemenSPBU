<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(ManagerSeeder::class);
        $this->call(PetugasSeeder::class);
        $this->call(CoaSeeder::class);
        $this->call(MasterBbmSeeder::class);
        $this->call(MasterBbmCoaPersediaanSeeder::class);
        $this->call(TangkiBbmSeeder::class);
        $this->call(PayrollSettingSeeder::class);
    }
}
