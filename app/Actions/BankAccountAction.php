<?php

namespace App\Actions;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Enums\AuditType;
use App\Models\BankAccount;
use App\Models\User;
use App\Services\Payments\PaystackService;

/**
 * Links a user's bank account via Paystack and retries any payouts left pending for it.
 */
class BankAccountAction
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly BankAccountRepositoryInterface $bankAccounts,
        private readonly PayoutRepositoryInterface $payouts,
        private readonly PayoutAction $payoutAction,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function register(User $user, string $bankCode, string $accountNumber): BankAccount
    {
        $recipientCode = $this->paystack->createRecipient($user, $bankCode, $accountNumber);

        $bankAccount = $this->bankAccounts->saveForUser($user, $bankCode, $accountNumber, $recipientCode);

        $this->auditLogs->record(
            $user,
            AuditType::BankAccountLinked,
            'Linked bank account',
            ['bank_code' => $bankCode, 'account_number_last_4' => substr($accountNumber, -4)],
        );

        // Any payout that was stuck waiting on a bank account can now go out.
        foreach ($this->payouts->pendingForUser($user) as $payout) {
            $this->payoutAction->attempt($payout);
        }

        return $bankAccount;
    }
}
