<?php

namespace App\Actions;

use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\Cache;

class UnlockAchievementAction
{
    public function __construct(
        private readonly UserAchievementRepositoryInterface $userAchievements,
    ) {}

    public function execute(User $user, Achievement $achievement): ?UserAchievement
    {
        // Guards the check-then-act unlock against concurrent queue workers
        // processing overlapping purchases for the same user; the unique
        // constraint on user_achievements is the last line of defense, but
        // this avoids the failed/retried job that would otherwise happen
        // when two workers race to unlock the same achievement.
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
