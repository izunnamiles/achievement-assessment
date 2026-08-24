<?php

namespace App\Contracts;

use App\Models\User;

interface PaymentGatewayInterface
{
    /**
     * Send a payout to the given user's account.
     *
     * @param  int  $amountInNaira  amount to pay, in Naira
     * @return bool  whether the payout was sent successfully
     */
    public function payout(User $user, int $amountInNaira, string $reason): bool;
}
