<?php

use App\Actions\RegisterBankAccountAction;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Services\Payments\PaystackService;

it('creates a paystack recipient then saves the bank account', function () {
    $user = makeUser(['id' => 1, 'name' => 'Jane Doe']);
    $bankAccount = makeBankAccount(['id' => 1, 'user_id' => 1]);

    $paystack = Mockery::mock(PaystackService::class);
    $paystack->shouldReceive('createRecipient')->once()->with($user, '058', '0123456789')->andReturn('RCP_123');

    $bankAccounts = Mockery::mock(BankAccountRepositoryInterface::class);
    $bankAccounts->shouldReceive('saveForUser')
        ->once()
        ->with($user, '058', '0123456789', 'RCP_123')
        ->andReturn($bankAccount);

    $result = (new RegisterBankAccountAction($paystack, $bankAccounts))->execute($user, '058', '0123456789');

    expect($result)->toBe($bankAccount);
});
