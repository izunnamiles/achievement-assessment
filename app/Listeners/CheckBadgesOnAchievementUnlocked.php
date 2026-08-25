<?php

namespace App\Listeners;

use App\Actions\UnlockBadgeAction;
use App\Events\AchievementUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckBadgesOnAchievementUnlocked implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly UnlockBadgeAction $unlockBadge,
    ) {}

    public function handle(AchievementUnlocked $event): void
    {
        $this->unlockBadge->unlockEligibleForUser($event->user);
    }
}
