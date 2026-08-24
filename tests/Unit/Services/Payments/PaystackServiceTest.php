<?php

use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Exceptions\PaystackRecipientCreationException;
use App\Services\Payments\PaystackService;
use Illuminate\Support\Facades\Http;

it('creates a recipient and returns the recipient code', function () {
    Http::fake([
        '*/transferrecipient' => Http::response([
            'status' => true,
            'data' => ['recipient_code' => 'RCP_123'],
        ], 200),
    ]);

    $user = makeUser(['id' => 1, 'name' => 'Jane Doe']);

    $paystack = new PaystackService(Mockery::mock(BankAccountRepositoryInterface::class));

    $recipientCode = $paystack->createRecipient($user, '058', '0123456789');

    expect($recipientCode)->toBe('RCP_123');

    Http::assertSent(fn ($request) => $request['bank_code'] === '058'
        && $request['account_number'] === '0123456789'
        && $request['name'] === 'Jane Doe');
});

it('throws when Paystack reports failure', function () {
    Http::fake([
        '*/transferrecipient' => Http::response(['status' => false, 'message' => 'Invalid account'], 400),
    ]);

    $user = makeUser(['id' => 1, 'name' => 'Jane Doe']);

    $paystack = new PaystackService(Mockery::mock(BankAccountRepositoryInterface::class));

    expect(fn () => $paystack->createRecipient($user, '058', '0000000000'))
        ->toThrow(PaystackRecipientCreationException::class);
});

it('pays out to the recipient on file and reports success', function () {
    Http::fake([
        '*/transfer' => Http::response(['status' => true], 200),
    ]);

    $user = makeUser(['id' => 1]);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1, 'paystack_recipient_code' => 'RCP_123']);

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('findForUser')->once()->with($user)->andReturn($bankAccount);

    $paystack = new PaystackService($bankAccounts);

    expect($paystack->payout($user, 300, 'Badge reward: Silver Achiever'))->toBeTrue();

    Http::assertSent(fn ($request) => $request['recipient'] === 'RCP_123' && $request['amount'] === 30000);
});

it('skips the payout when the user has no bank account on file', function () {
    Http::fake();

    $user = makeUser(['id' => 1]);

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('findForUser')->once()->with($user)->andReturn(null);

    $paystack = new PaystackService($bankAccounts);

    expect($paystack->payout($user, 300, 'Badge reward: Silver Achiever'))->toBeFalse();

    Http::assertNothingSent();
});

it('reports failure when Paystack rejects the transfer', function () {
    Http::fake([
        '*/transfer' => Http::response(['status' => false], 400),
    ]);

    $user = makeUser(['id' => 1]);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1, 'paystack_recipient_code' => 'RCP_123']);

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('findForUser')->once()->with($user)->andReturn($bankAccount);

    $paystack = new PaystackService($bankAccounts);

    expect($paystack->payout($user, 300, 'Badge reward: Silver Achiever'))->toBeFalse();
});
