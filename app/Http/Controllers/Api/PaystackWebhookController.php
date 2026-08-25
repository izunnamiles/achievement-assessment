<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PayoutRepositoryInterface $payouts): Response
    {
        $signature = $request->header('x-paystack-signature', '');
        $expected = hash_hmac('sha512', $request->getContent(), (string) config('services.paystack.secret_key'));

        abort_unless(hash_equals($expected, $signature), 401);

        $reference = $request->input('data.reference');
        $payout = $reference ? $payouts->findByReference($reference) : null;

        if ($payout) {
            match ($request->input('event')) {
                'transfer.success' => $payouts->markAsPaid($payout),
                'transfer.failed', 'transfer.reversed' => $payouts->markAsFailed($payout),
                default => null,
            };
        }

        return response()->noContent();
    }
}
