<?php

namespace App\Listeners;

use App\Actions\CheckBadgesAction;
use App\Events\AchievementUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckBadgesOnAchievementUnlocked implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CheckBadgesAction $checkBadges,
    ) {}

    public function handle(AchievementUnlocked $event): void
    {
        $this->checkBadges->execute($event->user);
    }
}
