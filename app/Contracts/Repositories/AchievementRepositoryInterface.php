<?php

namespace App\Contracts\Repositories;

use App\Enums\AchievementType;
use App\Models\Achievement;
use Illuminate\Support\Collection;

interface AchievementRepositoryInterface
{
    /**
     * @return Collection<int, Achievement>
     */
    public function allByType(AchievementType $type): Collection;

    public function findBySlug(string $slug): ?Achievement;
}