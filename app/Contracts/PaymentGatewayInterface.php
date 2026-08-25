<?php

namespace App\Contracts;

use App\Models\User;

interface PaymentGatewayInterface
{
    /**
     * Send a payout to the given user's account.
     *
     * @param  int  $amountInNaira  amount to pay, in Naira
     * @param  string  $reference  system-generated, unique per payout - passed
     *                             through to the gateway so a duplicate call
     *                             with the same reference is idempotent on
     *                             the gateway's end too.
     * @return bool  whether the payout was accepted for processing. This is
     *               NOT confirmation the money moved - use the gateway's own
     *               verification/webhook to know that.
     */
    public function payout(User $user, int $amountInNaira, string $reason, string $reference): bool;
}
