<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\Date;

class UserBadgeRepository implements UserBadgeRepositoryInterface
{
    public function hasUnlocked(User $user, Badge $badge): bool
    {
        return UserBadge::query()
            ->where('user_id', $user->id)
            ->where('badge_id', $badge->id)
            ->exists();
    }

    public function unlock(User $user, Badge $badge): UserBadge
    {
        return UserBadge::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'badge_id' => $badge->id,
            ],
            [
                'unlocked_at' => Date::now(),
            ],
        );
    }
}
