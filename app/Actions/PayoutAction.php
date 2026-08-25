<?php

namespace App\Actions;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Enums\AuditType;
use App\Models\Payout;
use App\Services\Payments\PaystackService;

/**
 * Sends a pending payout via the gateway (leaving it pending without a linked bank account), and later verifies its status with Paystack.
 */
class PayoutAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly BankAccountRepositoryInterface $bankAccounts,
        private readonly PayoutRepositoryInterface $payouts,
        private readonly PaystackService $paystack,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    /**
     * Try to send a pending payout. Used both when a payout is first created
     * (badge unlock) and to retry one that was stuck waiting on a bank
     * account (once BankAccountAction links one).
     */
    public function attempt(Payout $payout): void
    {
        if (! $this->bankAccounts->findForUser($payout->user)?->paystack_recipient_code) {
            // Nothing to send yet - stays pending until a bank account is
            // linked, at which point BankAccountAction retries it.
            $this->logAttempt($payout, 'skipped_no_bank_account');

            return;
        }

        $accepted = $this->paymentGateway->payout(
            $payout->user,
            (int) $payout->amount,
            $payout->reason,
            $payout->reference,
        );

        if (! $accepted) {
            $this->payouts->markAsFailed($payout);
        }

        $this->logAttempt($payout, $accepted ? 'sent_accepted' : 'sent_rejected');
    }

    /**
     * Checks a payout's status with Paystack and updates it to paid or failed.
     */
    public function verify(Payout $payout): void
    {
        $status = $this->paystack->verifyTransfer($payout->reference);

        $verified = match ($status) {
            'success' => $this->payouts->markAsPaid($payout),
            'failed', 'reversed' => $this->payouts->markAsFailed($payout),
            default => false,
        };

        if ($verified) {
            $this->auditLogs->record(
                $payout->user,
                AuditType::PayoutVerified,
                "Verified payout: {$status}",
                ['payout_id' => $payout->id, 'reference' => $payout->reference, 'status' => $status],
            );
        }
    }

    private function logAttempt(Payout $payout, string $outcome): void
    {
        $this->auditLogs->record(
            $payout->user,
            AuditType::PayoutAttempted,
            "Attempted payout: {$outcome}",
            ['payout_id' => $payout->id, 'reference' => $payout->reference, 'outcome' => $outcome],
        );
    }
}
