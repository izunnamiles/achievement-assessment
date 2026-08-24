<?php

namespace App\Actions;

use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\Cache;

class UnlockBadgeAction
{
    public function __construct(
        private readonly UserBadgeRepositoryInterface $userBadges,
    ) {}

    public function execute(User $user, Badge $badge): ?UserBadge
    {
        // Same rationale as UnlockAchievementAction: guards the check-then-act
        // unlock against concurrent queue workers racing on the same user.
        return Cache::lock("unlock-badge:{$user->id}:{$badge->id}", 10)->block(5, function () use ($user, $badge) {
            if ($this->userBadges->hasUnlocked($user, $badge)) {
                return null;
            }

            $unlocked = $this->userBadges->unlock($user, $badge);

            event(new BadgeUnlocked($badge->name, $user));

            return $unlocked;
        });
    }
}
