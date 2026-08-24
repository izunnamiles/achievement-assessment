<?php

namespace App\Listeners;

use App\Actions\CheckPurchaseAchievementsAction;
use App\Events\PurchaseMade;

class UnlockAchievementsOnPurchase
{
    public function __construct(
        private readonly CheckPurchaseAchievementsAction $checkPurchaseAchievements,
    ) {}

    public function handle(PurchaseMade $event): void
    {
        $this->checkPurchaseAchievements->execute($event->user);
    }
}
