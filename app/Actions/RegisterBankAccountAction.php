<?php

namespace App\Actions;

use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\Payments\PaystackService;

class RegisterBankAccountAction
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly BankAccountRepositoryInterface $bankAccounts,
    ) {}

    public function execute(User $user, string $bankCode, string $accountNumber): BankAccount
    {
        $recipientCode = $this->paystack->createRecipient($user, $bankCode, $accountNumber);

        return $this->bankAccounts->saveForUser($user, $bankCode, $accountNumber, $recipientCode);
    }
}
