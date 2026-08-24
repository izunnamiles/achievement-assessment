<?php

namespace App\Repositories;

use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Models\Badge;
use Illuminate\Support\Collection;

class BadgeRepository implements BadgeRepositoryInterface
{
    public function all(): Collection
    {
        return Badge::query()->orderBy('threshold')->get();
    }

    public function findBySlug(string $slug): ?Badge
    {
        return Badge::query()->where('slug', $slug)->first();
    }
}
