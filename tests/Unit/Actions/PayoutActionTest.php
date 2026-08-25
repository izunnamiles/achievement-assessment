<?php

use App\Actions\PayoutAction;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Services\Payments\PaystackService;

it('leaves the payout untouched when the user has no bank account on file', function () {
    $user = makeUser(['id' => 1]);
    $payout = makePayout(['id' => 1, 'user_id' => 1, 'amount' => 300, 'reason' => 'Badge reward: First Steps', 'reference' => 'ref-1'])
        ->setRelation('user', $user);

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('findForUser')->once()->with($user)->andReturn(null);

    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldNotReceive('payout');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldNotReceive('markAsFailed');

    (new PayoutAction($paymentGateway, $bankAccounts, $payouts, Mockery::mock(PaystackService::class)))->attempt($payout);
});

it('leaves the payout pending when the gateway accepts it', function () {
    $user = makeUser(['id' => 1]);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1, 'paystack_recipient_code' => 'RCP_123']);
    $payout = makePayout(['id' => 1, 'user_id' => 1, 'amount' => 300, 'reason' => 'Badge reward: First Steps', 'reference' => 'ref-1'])
        ->setRelation('user', $user);

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('findForUser')->once()->with($user)->andReturn($bankAccount);

    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('payout')->once()->with($user, 300, 'Badge reward: First Steps', 'ref-1')->andReturn(true);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldNotReceive('markAsFailed');

    (new PayoutAction($paymentGateway, $bankAccounts, $payouts, Mockery::mock(PaystackService::class)))->attempt($payout);
});

it('marks the payout as failed when the gateway rejects it', function () {
    $user = makeUser(['id' => 1]);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1, 'paystack_recipient_code' => 'RCP_123']);
    $payout = makePayout(['id' => 1, 'user_id' => 1, 'amount' => 300, 'reason' => 'Badge reward: First Steps', 'reference' => 'ref-1'])
        ->setRelation('user', $user);

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('findForUser')->once()->andReturn($bankAccount);

    $paymentGateway = Mockery::mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('payout')->once()->andReturn(false);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsFailed')->once()->with($payout);

    (new PayoutAction($paymentGateway, $bankAccounts, $payouts, Mockery::mock(PaystackService::class)))->attempt($payout);
});

it('marks the payout as paid when Paystack reports success', function () {
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1']);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->with('ref-1')->andReturn('success');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsPaid')->once()->with($payout);

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
    );

    $action->verify($payout);
});

it('marks the payout as failed when Paystack reports failure', function () {
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1']);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->andReturn('failed');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsFailed')->once()->with($payout);

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
    );

    $action->verify($payout);
});

it('marks the payout as failed when Paystack reports the transfer was reversed', function () {
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1']);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->andReturn('reversed');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsFailed')->once()->with($payout);

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
    );

    $action->verify($payout);
});

it('leaves the payout untouched while Paystack still reports it pending', function () {
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1']);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->andReturn('pending');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldNotReceive('markAsPaid');
    $payouts->shouldNotReceive('markAsFailed');

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
    );

    $action->verify($payout);
});
