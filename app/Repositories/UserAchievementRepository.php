<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class UserAchievementRepository implements UserAchievementRepositoryInterface
{
    public function hasUnlocked(User $user, Achievement $achievement): bool
    {
        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->exists();
    }

    public function unlock(User $user, Achievement $achievement): UserAchievement
    {
        return UserAchievement::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
            ],
            [
                'unlocked_at' => Date::now(),
            ],
        );
    }

    public function unlockedForUser(User $user): Collection
    {
        return UserAchievement::query()
            ->select('id', 'achievement_id', 'unlocked_at')
            ->with('achievement:id,name,slug,description,type,threshold')
            ->where('user_id', $user->id)
            ->get();
    }

    public function countForUser(User $user): int
    {
        return UserAchievement::query()->where('user_id', $user->id)->count();
    }
}
