<?php

namespace App\Actions;

use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Models\User;

class CheckBadgesAction
{
    public function __construct(
        private readonly UserAchievementRepositoryInterface $userAchievements,
        private readonly BadgeRepositoryInterface $badges,
        private readonly UnlockBadgeAction $unlockBadge,
    ) {}

    public function execute(User $user): void
    {
        $achievementCount = $this->userAchievements->countForUser($user);

        $eligible = $this->badges->all()->where('threshold', '<=', $achievementCount);

        foreach ($eligible as $badge) {
            $this->unlockBadge->execute($user, $badge);
        }
    }
}
