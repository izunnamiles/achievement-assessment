<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserAchievementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request, UserAchievementRepositoryInterface $userAchievements): JsonResponse
    {
        return response()->json([
            'message' => 'Achievements retrieved successfully.',
            'data' => UserAchievementResource::collection($userAchievements->unlockedForUser($request->user())),
        ]);
    }
}
