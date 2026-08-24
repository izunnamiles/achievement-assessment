<?php

namespace App\Listeners;

use App\Actions\CheckPurchaseAchievementsAction;
use App\Events\PurchaseMade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UnlockAchievementsOnPurchase implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CheckPurchaseAchievementsAction $checkPurchaseAchievements,
    ) {}

    public function handle(PurchaseMade $event): void
    {
        $this->checkPurchaseAchievements->execute($event->user);
    }
}
