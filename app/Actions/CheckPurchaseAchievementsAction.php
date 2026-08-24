<?php

namespace App\Actions;

use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Enums\AchievementType;
use App\Models\User;

class CheckPurchaseAchievementsAction
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchases,
        private readonly AchievementRepositoryInterface $achievements,
        private readonly UnlockAchievementAction $unlockAchievement,
    ) {}

    public function execute(User $user): void
    {
        $purchaseCount = $this->purchases->countForUser($user);

        $eligible = $this->achievements
            ->allByType(AchievementType::Purchases)
            ->where('threshold', '<=', $purchaseCount);

        foreach ($eligible as $achievement) {
            $this->unlockAchievement->execute($user, $achievement);
        }
    }
}
