<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackPaymentGateway implements PaymentGatewayInterface
{
    public function payout(User $user, int $amountInNaira, string $reason): bool
    {
        if (! $user->paystack_recipient_code) {
            Log::warning("Skipped Paystack payout for user [{$user->id}]: no recipient code on file.");

            return false;
        }

        $response = Http::baseUrl(config('services.paystack.base_url'))
            ->withToken(config('services.paystack.secret_key'))
            ->post('/transfer', [
                'source' => 'balance',
                'amount' => $amountInNaira * 100,
                'recipient' => $user->paystack_recipient_code,
                'reason' => $reason,
            ]);

        if ($response->failed()) {
            Log::error("Paystack payout failed for user [{$user->id}]: {$response->body()}");
        }

        return $response->successful();
    }
}
