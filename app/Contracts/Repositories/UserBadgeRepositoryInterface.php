<?php

namespace App\Contracts\Repositories;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;

interface UserBadgeRepositoryInterface
{
    public function hasUnlocked(User $user, Badge $badge): bool;

    public function unlock(User $user, Badge $badge): UserBadge;
}
