<?php

namespace App\Repositories;

use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Models\Purchase;
use App\Models\User;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    public function create(User $user, array $attributes): Purchase
    {
        return $user->purchases()->create($attributes);
    }

    public function countForUser(User $user): int
    {
        return $user->purchases()->count();
    }
}
