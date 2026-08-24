<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Exceptions\PaystackRecipientCreationException;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService implements PaymentGatewayInterface
{
    public function __construct(
        private readonly BankAccountRepositoryInterface $bankAccounts,
    ) {}

    /**
     * Register the user's bank account with Paystack as a transfer recipient,
     * returning the recipient code that later payouts are sent to.
     */
    public function createRecipient(User $user, string $bankCode, string $accountNumber): string
    {
        $response = Http::baseUrl(config('services.paystack.base_url'))
            ->withToken(config('services.paystack.secret_key'))
            ->post('/transferrecipient', [
                'type' => 'nuban',
                'name' => $user->name,
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'currency' => 'NGN',
            ]);

        if ($response->failed() || ! $response->json('status')) {
            throw new PaystackRecipientCreationException(
                "Failed to create Paystack recipient for user [{$user->id}]: {$response->body()}",
            );
        }

        return $response->json('data.recipient_code');
    }

    public function payout(User $user, int $amountInNaira, string $reason): bool
    {
        $bankAccount = $this->bankAccounts->findForUser($user);

        if (! $bankAccount?->paystack_recipient_code) {
            Log::warning("Skipped Paystack payout for user [{$user->id}]: no recipient code on file.");

            return false;
        }

        $response = Http::baseUrl(config('services.paystack.base_url'))
            ->withToken(config('services.paystack.secret_key'))
            ->post('/transfer', [
                'source' => 'balance',
                'amount' => $amountInNaira * 100,
                'recipient' => $bankAccount->paystack_recipient_code,
                'reason' => $reason,
            ]);

        if ($response->failed()) {
            Log::error("Paystack payout failed for user [{$user->id}]: {$response->body()}");
        }

        return $response->successful();
    }
}
