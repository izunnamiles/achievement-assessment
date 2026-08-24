<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tiers = [
            1 => 'First Steps',
            2 => 'Bronze Achiever',
            3 => 'Silver Achiever',
            4 => 'Gold Achiever',
            5 => 'Platinum Achiever',
            6 => 'Diamond Achiever',
            7 => 'Elite Achiever',
            8 => 'Master Achiever',
            9 => 'Grandmaster Achiever',
            10 => 'Legendary Achiever',
        ];

        foreach ($tiers as $threshold => $name) {
            Badge::query()->updateOrCreate(
                ['slug' => str($name)->slug()],
                [
                    'name' => $name,
                    'description' => "Unlocked {$threshold} achievement(s).",
                    'threshold' => $threshold,
                ],
            );
        }
    }
}
