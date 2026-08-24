<?php

namespace App\Actions;

use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;

class UnlockBadgeAction
{
    public function __construct(
        private readonly UserBadgeRepositoryInterface $userBadges,
    ) {}

    public function execute(User $user, Badge $badge): ?UserBadge
    {
        if ($this->userBadges->hasUnlocked($user, $badge)) {
            return null;
        }

        $unlocked = $this->userBadges->unlock($user, $badge);

        event(new BadgeUnlocked($badge->name, $user));

        return $unlocked;
    }
}
