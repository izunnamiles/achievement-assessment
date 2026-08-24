<?php

namespace Database\Seeders;

use App\Enums\AchievementType;
use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'First Purchase',
                'slug' => 'first-purchase',
                'description' => 'Made your first purchase.',
                'type' => AchievementType::Purchases,
                'threshold' => 1,
            ],
            [
                'name' => '5 Purchases',
                'slug' => '5-purchases',
                'description' => 'Made 5 purchases.',
                'type' => AchievementType::Purchases,
                'threshold' => 5,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::query()->updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement,
            );
        }
    }
}
