<?php

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;

function paystackWebhookPayload(array $data): array
{
    return ['event' => $data['event'], 'data' => ['reference' => $data['reference']]];
}

function signedPaystackWebhookRequest(Tests\TestCase $test, array $payload): Illuminate\Testing\TestResponse
{
    $body = json_encode($payload);
    $signature = hash_hmac('sha512', $body, config('services.paystack.secret_key'));

    return $test->postJson('/api/paystack/webhook', $payload, ['x-paystack-signature' => $signature]);
}

test('a transfer.success event marks the matching payout as paid', function () {
    $user = User::factory()->create();
    $payout = Payout::factory()->create(['user_id' => $user->id, 'reference' => 'ref-1', 'status' => PayoutStatus::Pending]);

    $response = signedPaystackWebhookRequest($this, paystackWebhookPayload([
        'event' => 'transfer.success',
        'reference' => 'ref-1',
    ]));

    $response->assertNoContent();
    expect($payout->refresh()->status)->toBe(PayoutStatus::Paid);
});

test('a transfer.failed event marks the matching payout as failed', function () {
    $user = User::factory()->create();
    $payout = Payout::factory()->create(['user_id' => $user->id, 'reference' => 'ref-1', 'status' => PayoutStatus::Pending]);

    $response = signedPaystackWebhookRequest($this, paystackWebhookPayload([
        'event' => 'transfer.failed',
        'reference' => 'ref-1',
    ]));

    $response->assertNoContent();
    expect($payout->refresh()->status)->toBe(PayoutStatus::Failed);
});

test('a request with an invalid signature is rejected', function () {
    $payload = paystackWebhookPayload(['event' => 'transfer.success', 'reference' => 'ref-1']);

    $this->postJson('/api/paystack/webhook', $payload, ['x-paystack-signature' => 'not-the-real-signature'])
        ->assertStatus(401);
});

test('an unknown reference is a no-op, not an error', function () {
    $response = signedPaystackWebhookRequest($this, paystackWebhookPayload([
        'event' => 'transfer.success',
        'reference' => 'no-such-reference',
    ]));

    $response->assertNoContent();
});
