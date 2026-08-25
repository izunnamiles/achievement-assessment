<?php

use App\Actions\PayoutAction;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Enums\AuditType;
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

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::PayoutAttempted,
        'Attempted payout: skipped_no_bank_account',
        ['payout_id' => 1, 'reference' => 'ref-1', 'outcome' => 'skipped_no_bank_account'],
    );

    (new PayoutAction($paymentGateway, $bankAccounts, $payouts, Mockery::mock(PaystackService::class), $auditLogs))->attempt($payout);
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

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::PayoutAttempted,
        'Attempted payout: sent_accepted',
        ['payout_id' => 1, 'reference' => 'ref-1', 'outcome' => 'sent_accepted'],
    );

    (new PayoutAction($paymentGateway, $bankAccounts, $payouts, Mockery::mock(PaystackService::class), $auditLogs))->attempt($payout);
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

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::PayoutAttempted,
        'Attempted payout: sent_rejected',
        ['payout_id' => 1, 'reference' => 'ref-1', 'outcome' => 'sent_rejected'],
    );

    (new PayoutAction($paymentGateway, $bankAccounts, $payouts, Mockery::mock(PaystackService::class), $auditLogs))->attempt($payout);
});

it('marks the payout as paid when Paystack reports success', function () {
    $user = makeUser(['id' => 1]);
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1'])->setRelation('user', $user);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->with('ref-1')->andReturn('success');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsPaid')->once()->with($payout)->andReturn(true);

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::PayoutVerified,
        'Verified payout: success',
        ['payout_id' => 1, 'reference' => 'ref-1', 'status' => 'success'],
    );

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
        $auditLogs,
    );

    $action->verify($payout);
});

it('marks the payout as failed when Paystack reports failure', function () {
    $user = makeUser(['id' => 1]);
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1'])->setRelation('user', $user);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->andReturn('failed');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsFailed')->once()->with($payout)->andReturn(true);

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::PayoutVerified,
        'Verified payout: failed',
        ['payout_id' => 1, 'reference' => 'ref-1', 'status' => 'failed'],
    );

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
        $auditLogs,
    );

    $action->verify($payout);
});

it('marks the payout as failed when Paystack reports the transfer was reversed', function () {
    $user = makeUser(['id' => 1]);
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1'])->setRelation('user', $user);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->andReturn('reversed');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsFailed')->once()->with($payout)->andReturn(true);

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::PayoutVerified,
        'Verified payout: reversed',
        ['payout_id' => 1, 'reference' => 'ref-1', 'status' => 'reversed'],
    );

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
        $auditLogs,
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

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldNotReceive('record');

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
        $auditLogs,
    );

    $action->verify($payout);
});

it('does not log a verification when markAsPaid loses the race to another process', function () {
    $user = makeUser(['id' => 1]);
    $payout = makePayout(['id' => 1, 'reference' => 'ref-1'])->setRelation('user', $user);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('verifyTransfer')->once()->andReturn('success');

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('markAsPaid')->once()->with($payout)->andReturn(false);

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldNotReceive('record');

    $action = new PayoutAction(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(BankAccountRepositoryInterface::class),
        $payouts,
        $paystack,
        $auditLogs,
    );

    $action->verify($payout);
});
