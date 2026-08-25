<?php

namespace App\Listeners;

use App\Actions\UnlockAchievementAction;
use App\Events\PurchaseMade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UnlockAchievementsOnPurchase implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly UnlockAchievementAction $unlockAchievement,
    ) {}

    public function handle(PurchaseMade $event): void
    {
        $this->unlockAchievement->unlockEligibleForUser($event->user);
    }
}
