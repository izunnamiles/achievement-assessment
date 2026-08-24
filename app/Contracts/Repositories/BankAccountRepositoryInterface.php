<?php

namespace App\Contracts\Repositories;

use App\Models\BankAccount;
use App\Models\User;

interface BankAccountRepositoryInterface
{
    public function findForUser(User $user): ?BankAccount;

    public function saveForUser(User $user, string $bankCode, string $accountNumber, string $recipientCode): BankAccount;
}
