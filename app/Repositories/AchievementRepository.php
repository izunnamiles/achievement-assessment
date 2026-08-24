<?php

namespace App\Repositories;

use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Enums\AchievementType;
use App\Models\Achievement;
use Illuminate\Support\Collection;

class AchievementRepository implements AchievementRepositoryInterface
{
    public function allByType(AchievementType $type): Collection
    {
        return Achievement::query()
            ->where('type', $type)
            ->orderBy('threshold')
            ->get();
    }

    public function findBySlug(string $slug): ?Achievement
    {
        return Achievement::query()->where('slug', $slug)->first();
    }
}
