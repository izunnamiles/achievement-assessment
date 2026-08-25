<?php

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('it verifies every pending payout and updates their status', function () {
    $user = User::factory()->create();

    $paid = Payout::factory()->create(['user_id' => $user->id, 'reference' => 'ref-paid', 'status' => PayoutStatus::Pending]);
    $failed = Payout::factory()->create(['user_id' => $user->id, 'reference' => 'ref-failed', 'status' => PayoutStatus::Pending]);
    $stillPending = Payout::factory()->create(['user_id' => $user->id, 'reference' => 'ref-pending', 'status' => PayoutStatus::Pending]);
    $alreadyPaid = Payout::factory()->paid()->create(['user_id' => $user->id, 'reference' => 'ref-already-paid']);

    Http::fake([
        '*/transfer/verify/ref-paid' => Http::response(['status' => true, 'data' => ['status' => 'success']]),
        '*/transfer/verify/ref-failed' => Http::response(['status' => true, 'data' => ['status' => 'failed']]),
        '*/transfer/verify/ref-pending' => Http::response(['status' => true, 'data' => ['status' => 'pending']]),
    ]);

    $this->artisan('payouts:verify')->assertExitCode(0);

    expect($paid->refresh()->status)->toBe(PayoutStatus::Paid)
        ->and($failed->refresh()->status)->toBe(PayoutStatus::Failed)
        ->and($stillPending->refresh()->status)->toBe(PayoutStatus::Pending)
        ->and($alreadyPaid->refresh()->status)->toBe(PayoutStatus::Paid);

    Http::assertSentCount(3);
});
