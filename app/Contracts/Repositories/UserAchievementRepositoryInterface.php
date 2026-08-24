<?php

namespace App\Contracts\Repositories;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;

interface UserAchievementRepositoryInterface
{
    public function hasUnlocked(User $user, Achievement $achievement): bool;

    public function unlock(User $user, Achievement $achievement): UserAchievement;

    /**
     * @return Collection<int, UserAchievement>
     */
    public function unlockedForUser(User $user): Collection;

    public function countForUser(User $user): int;
}
