<?php

use App\Actions\BankAccountAction;
use App\Actions\PayoutAction;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Services\Payments\PaystackService;
use Illuminate\Support\Collection;

it('creates a paystack recipient, saves the bank account, and retries any pending payouts', function () {
    $user = makeUser(['id' => 1, 'name' => 'Jane Doe']);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1]);
    $payoutOne = makePayout(['id' => 1, 'user_id' => 1]);
    $payoutTwo = makePayout(['id' => 2, 'user_id' => 1]);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('createRecipient')->once()->with($user, '058', '0123456789')->andReturn('RCP_123');

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('saveForUser')
        ->once()
        ->with($user, '058', '0123456789', 'RCP_123')
        ->andReturn($bankAccount);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('pendingForUser')->once()->with($user)->andReturn(new Collection([$payoutOne, $payoutTwo]));

    $payoutAction = Mockery::mock(PayoutAction::class);
    $payoutAction->shouldReceive('attempt')->once()->with($payoutOne);
    $payoutAction->shouldReceive('attempt')->once()->with($payoutTwo);

    $result = (new BankAccountAction($paystack, $bankAccounts, $payouts, $payoutAction))
        ->register($user, '058', '0123456789');

    expect($result)->toBe($bankAccount);
});

it('does nothing extra when there are no pending payouts to retry', function () {
    $user = makeUser(['id' => 1, 'name' => 'Jane Doe']);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1]);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('createRecipient')->once()->andReturn('RCP_123');

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('saveForUser')->once()->andReturn($bankAccount);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('pendingForUser')->once()->with($user)->andReturn(new Collection());

    $payoutAction = Mockery::mock(PayoutAction::class);
    $payoutAction->shouldNotReceive('attempt');

    (new BankAccountAction($paystack, $bankAccounts, $payouts, $payoutAction))
        ->register($user, '058', '0123456789');
});
