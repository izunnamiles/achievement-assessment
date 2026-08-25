<?php

namespace App\Actions;

use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\Cache;

/**
 * Checks a user's achievement count and unlocks each badge whose threshold is now met, dispatching BadgeUnlocked.
 */
class UnlockBadgeAction
{
    public function __construct(
        private readonly UserAchievementRepositoryInterface $userAchievements,
        private readonly BadgeRepositoryInterface $badges,
        private readonly UserBadgeRepositoryInterface $userBadges,
    ) {}

    /**
     * Unlocks every badge whose achievement-count threshold the user now meets.
     */
    public function unlockEligibleForUser(User $user): void
    {
        $achievementCount = $this->userAchievements->countForUser($user);

        $eligible = $this->badges->all()->where('threshold', '<=', $achievementCount);

        foreach ($eligible as $badge) {
            $this->unlock($user, $badge);
        }
    }

    /**
     * Unlocks a single badge for a user once, dispatching BadgeUnlocked.
     */
    public function unlock(User $user, Badge $badge): ?UserBadge
    {
        // Same rationale and CACHE_STORE caveat as UnlockAchievementAction.
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
