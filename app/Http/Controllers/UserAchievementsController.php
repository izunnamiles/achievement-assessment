<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class UserAchievementsController extends Controller
{
    public function show(
        User $user,
        AchievementRepositoryInterface $achievements,
        UserAchievementRepositoryInterface $userAchievements,
    ): JsonResponse {
        $unlocked = $userAchievements->unlockedForUser($user);
        $unlockedIds = $unlocked->pluck('achievement.id')->all();

        $nextAvailable = $achievements->all()
            ->groupBy(fn (Achievement $achievement) => $achievement->type->value)
            ->map(fn (Collection $group) => $group->first(
                fn (Achievement $achievement) => ! in_array($achievement->id, $unlockedIds, true),
            ))
            ->filter()
            ->pluck('name');

        return response()->json([
            'unlocked_achievements' => $unlocked->pluck('achievement.name')->values(),
            'next_available_achievements' => $nextAvailable->values(),
        ]);
    }
}
