<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Exceptions\PaystackRecipientCreationException;
use App\Exceptions\PaystackVerificationException;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService implements PaymentGatewayInterface
{
    private readonly string $baseUrl;
    private readonly string $secretKey;

    public function __construct(private readonly BankAccountRepositoryInterface $bankAccounts)
    {
        $this->baseUrl = config('services.paystack.base_url');
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Register the user's bank account with Paystack as a transfer recipient,
     * returning the recipient code that later payouts are sent to.
     */
    public function createRecipient(User $user, string $bankCode, string $accountNumber): string
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
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

    public function payout(User $user, int $amountInNaira, string $reason, string $reference): bool
    {
        $bankAccount = $this->bankAccounts->findForUser($user);

        if (! $bankAccount?->paystack_recipient_code) {
            Log::warning("Skipped Paystack payout for user [{$user->id}]: no recipient code on file.");
            return false;
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->post('/transfer', [
                'source' => 'balance',
                'amount' => $amountInNaira * 100,
                'recipient' => $bankAccount->paystack_recipient_code,
                'reason' => $reason,
                'reference' => $reference,
            ]);

        if ($response->failed()) {
            Log::error("Paystack payout failed for user [{$user->id}]: {$response->body()}");
            // Note: we don't throw an exception here because this is a queued listener, and we don't want to retry indefinitely on a failed payout. The job will be retried a few times by the queue worker, but if it keeps failing, we log the error and move on.
        }

        return $response->successful();
    }

    public function verifyTransfer(string $reference): string
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->get("/transfer/verify/{$reference}");

        if ($response->failed() || ! $response->json('status')) {
            throw new PaystackVerificationException(
                "Failed to verify Paystack transfer [{$reference}]: {$response->body()}",
            );
        }

        return $response->json('data.status', 'pending');
    }
}
