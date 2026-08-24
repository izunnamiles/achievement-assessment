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

    /**
     * All achievements, ordered by type then threshold.
     *
     * @return Collection<int, Achievement>
     */
    public function all(): Collection;

    public function findBySlug(string $slug): ?Achievement;
}