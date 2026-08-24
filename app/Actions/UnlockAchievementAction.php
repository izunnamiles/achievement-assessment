<?php

namespace App\Actions;

use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;

class UnlockAchievementAction
{
    public function __construct(
        private readonly UserAchievementRepositoryInterface $userAchievements,
    ) {}

    public function execute(User $user, Achievement $achievement): ?UserAchievement
    {
        if ($this->userAchievements->hasUnlocked($user, $achievement))
            return null;

        $unlocked = $this->userAchievements->unlock($user, $achievement);

        event(new AchievementUnlocked($achievement->name, $user));

        return $unlocked;
    }
}
