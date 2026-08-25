<?php

namespace App\Repositories;

use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class PayoutRepository implements PayoutRepositoryInterface
{
    public function firstOrCreatePending(User $user, int $amountInNaira, string $reason): Payout
    {
        return Payout::query()->firstOrCreate(
            ['user_id' => $user->id, 'reason' => $reason],
            [
                'reference' => (string) Str::uuid(),
                'amount' => $amountInNaira,
                'status' => PayoutStatus::Pending,
            ],
        );
    }

    public function findByReference(string $reference): ?Payout
    {
        return Payout::query()->where('reference', $reference)->first();
    }

    public function pending(): Collection
    {
        return Payout::query()->where('status', PayoutStatus::Pending)->get();
    }

    public function pendingForUser(User $user): Collection
    {
        return Payout::query()
            ->where('user_id', $user->id)
            ->where('status', PayoutStatus::Pending)
            ->get();
    }

    public function markAsPaid(Payout $payout): bool
    {
        return Payout::query()
            ->whereKey($payout->id)
            ->where('status', PayoutStatus::Pending)
            ->update(['status' => PayoutStatus::Paid, 'verified_at' => Date::now()]) > 0;
    }

    public function markAsFailed(Payout $payout): bool
    {
        return Payout::query()
            ->whereKey($payout->id)
            ->where('status', PayoutStatus::Pending)
            ->update(['status' => PayoutStatus::Failed]) > 0;
    }
}
