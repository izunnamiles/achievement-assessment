<?php

namespace App\Repositories;

use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Models\BankAccount;
use App\Models\User;

class BankAccountRepository implements BankAccountRepositoryInterface
{
    public function findForUser(User $user): ?BankAccount
    {
        return BankAccount::query()->where('user_id', $user->id)->first();
    }

    public function saveForUser(User $user, string $bankCode, string $accountNumber, string $recipientCode): BankAccount
    {
        return BankAccount::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bank_code' => $bankCode,
                'account_number' => $accountNumber,
                'paystack_recipient_code' => $recipientCode,
            ],
        );
    }
}
