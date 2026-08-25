<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class UserAchievementsController extends Controller
{
    public function show(
        User $user,
        AchievementRepositoryInterface $achievements,
        UserAchievementRepositoryInterface $userAchievements,
        BadgeRepositoryInterface $badges,
    ): JsonResponse {
        $unlocked = $userAchievements->unlockedForUser($user);
        $unlockedIds = $unlocked->pluck('achievement.id')->all();
        $achievementCount = $unlocked->count();

        $nextAvailable = $achievements->all()
            ->groupBy(fn (Achievement $achievement) => $achievement->type->value)
            ->map(fn (Collection $group) => $group->first(
                fn (Achievement $achievement) => ! in_array($achievement->id, $unlockedIds, true),
            ))
            ->filter()
            ->pluck('name');

        // Badge::all() is ordered by threshold ascending, so the last one the
        // user meets is their current badge, and the first one they don't is
        // the next badge to work towards.
        $allBadges = $badges->all();

        $currentBadge = $allBadges->last(fn (Badge $badge) => $badge->threshold <= $achievementCount);
        $nextBadge = $allBadges->first(fn (Badge $badge) => $badge->threshold > $achievementCount);

        return response()->json([
            'unlocked_achievements' => $unlocked->pluck('achievement.name')->values(),
            'next_available_achievements' => $nextAvailable->values(),
            'current_badge' => $currentBadge?->name ?? '',
            'next_badge' => $nextBadge?->name ?? '',
            'remaining_to_unlock_next_badge' => $nextBadge ? $nextBadge->threshold - $achievementCount : 0,
        ]);
    }
}
