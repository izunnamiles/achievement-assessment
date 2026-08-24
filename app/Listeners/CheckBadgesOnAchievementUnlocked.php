<?php

namespace App\Listeners;

use App\Actions\CheckBadgesAction;
use App\Events\AchievementUnlocked;

class CheckBadgesOnAchievementUnlocked
{
    public function __construct(
        private readonly CheckBadgesAction $checkBadges,
    ) {}

    public function handle(AchievementUnlocked $event): void
    {
        $this->checkBadges->execute($event->user);
    }
}
