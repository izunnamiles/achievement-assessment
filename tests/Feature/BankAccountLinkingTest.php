<?php

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('an authenticated user can link their bank account', function () {
    Http::fake([
        '*/transferrecipient' => Http::response([
            'status' => true,
            'data' => ['recipient_code' => 'RCP_123'],
        ], 200),
    ]);

    $token = auth('api')->login($this->user);

    $this->withToken($token)
        ->postJson('/api/bank-account', [
            'bank_code' => '058',
            'account_number' => '0123456789',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Bank account linked successfully.']);

    $bankAccount = $this->user->bankAccount;

    expect($bankAccount)->not->toBeNull()
        ->and($bankAccount->paystack_recipient_code)->toBe('RCP_123')
        ->and($bankAccount->bank_code)->toBe('058')
        ->and($bankAccount->account_number)->toBe('0123456789');
});

test('linking fails with a 422 when paystack rejects the account', function () {
    Http::fake([
        '*/transferrecipient' => Http::response(['status' => false, 'message' => 'Invalid account'], 400),
    ]);

    $token = auth('api')->login($this->user);

    $this->withToken($token)
        ->postJson('/api/bank-account', [
            'bank_code' => '058',
            'account_number' => '0000000000',
        ])
        ->assertStatus(422);

    expect($this->user->bankAccount)->toBeNull();
});

test('linking a bank account auto-initiates any payout that was stuck waiting for one', function () {
    Http::fake([
        '*/transferrecipient' => Http::response(['status' => true, 'data' => ['recipient_code' => 'RCP_123']], 200),
        '*/transfer' => Http::response(['status' => true], 200),
    ]);

    $payout = Payout::factory()->create(['user_id' => $this->user->id, 'status' => PayoutStatus::Pending]);
    $token = auth('api')->login($this->user);

    $this->withToken($token)
        ->postJson('/api/bank-account', [
            'bank_code' => '058',
            'account_number' => '0123456789',
        ])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/transfer')
        && ! str_contains($request->url(), '/transferrecipient')
        && $request['reference'] === $payout->reference);
});

test('account_number must be exactly 10 digits', function () {
    $token = auth('api')->login($this->user);

    $this->withToken($token)
        ->postJson('/api/bank-account', [
            'bank_code' => '058',
            'account_number' => '123',
        ])
        ->assertStatus(422);
});
