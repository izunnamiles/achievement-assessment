<?php

namespace App\Contracts\Repositories;

use App\Models\Purchase;
use App\Models\User;

interface PurchaseRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Purchase;

    public function countForUser(User $user): int;
}
