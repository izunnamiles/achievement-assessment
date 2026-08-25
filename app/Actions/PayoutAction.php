<?php

namespace App\Actions;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\PayoutRepositoryInterface;
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
    }

    /**
     * Checks a payout's status with Paystack and updates it to paid or failed.
     */
    public function verify(Payout $payout): void
    {
        $status = $this->paystack->verifyTransfer($payout->reference);

        match ($status) {
            'success' => $this->payouts->markAsPaid($payout),
            'failed', 'reversed' => $this->payouts->markAsFailed($payout),
            default => null,
        };
    }
}
