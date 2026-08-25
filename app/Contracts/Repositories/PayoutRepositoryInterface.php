<?php

namespace App\Contracts\Repositories;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;

interface PayoutRepositoryInterface
{
    /**
     * Find the existing payout for this exact user + reason, or create a new
     * pending one with a fresh, system-generated reference. Keyed on
     * (user_id, reason) so a retried/redelivered job finds the existing
     * record instead of creating (and paying) a second one.
     */
    public function firstOrCreatePending(User $user, int $amountInNaira, string $reason): Payout;

    public function findByReference(string $reference): ?Payout;

    /**
     * @return Collection<int, Payout>
     */
    public function pending(): Collection;

    /**
     * @return Collection<int, Payout>
     */
    public function pendingForUser(User $user): Collection;

    /**
     * @return bool  true if this payout was pending and is now marked paid.
     */
    public function markAsPaid(Payout $payout): bool;

    /**
     * @return bool  true if this payout was pending and is now marked failed.
     */
    public function markAsFailed(Payout $payout): bool;
}
