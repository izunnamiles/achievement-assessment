<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password@123',
            ],
        );

        $this->call(AchievementSeeder::class);
        $this->call(BadgeSeeder::class);
        $this->call(SystemSettingSeeder::class);
        $this->call(ProductSeeder::class);
    }
}
