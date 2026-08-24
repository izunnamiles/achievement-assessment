<?php

namespace App\Contracts\Repositories;

use App\Models\Badge;
use Illuminate\Support\Collection;

interface BadgeRepositoryInterface
{
    /**
     * @return Collection<int, Badge>
     */
    public function all(): Collection;

    public function findBySlug(string $slug): ?Badge;
}
