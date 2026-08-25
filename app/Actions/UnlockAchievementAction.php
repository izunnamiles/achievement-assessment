<?php

namespace App\Actions;

use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Enums\AchievementType;
use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\Cache;

/**
 * Checks a user's purchase achievements and unlocks each one whose threshold is now met, dispatching AchievementUnlocked.
 */
class UnlockAchievementAction
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchases,
        private readonly AchievementRepositoryInterface $achievements,
        private readonly UserAchievementRepositoryInterface $userAchievements,
    ) {}

    /**
     * Unlocks every purchase achievement whose threshold the user's purchase count now meets.
     */
    public function unlockEligibleForUser(User $user): void
    {
        $purchaseCount = $this->purchases->countForUser($user);

        $eligible = $this->achievements
            ->allByType(AchievementType::Purchases)
            ->where('threshold', '<=', $purchaseCount);

        foreach ($eligible as $achievement) {
            $this->unlock($user, $achievement);
        }
    }

    /**
     * Unlocks a single achievement for a user once, dispatching AchievementUnlocked.
     */
    public function unlock(User $user, Achievement $achievement): ?UserAchievement
    {
        // Guards against concurrent queue workers double-unlocking the same achievement; only cross-process with a shared-storage cache driver (this app uses CACHE_STORE=database).
        return Cache::lock("unlock-achievement:{$user->id}:{$achievement->id}", 10)->block(5, function () use ($user, $achievement) {
            if ($this->userAchievements->hasUnlocked($user, $achievement)) {
                return null;
            }

            $unlocked = $this->userAchievements->unlock($user, $achievement);

            event(new AchievementUnlocked($achievement->name, $user));

            return $unlocked;
        });
    }
}
