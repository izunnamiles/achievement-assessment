<?php

namespace App\Console\Commands;

use App\Actions\PayoutAction;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use Illuminate\Console\Command;

class VerifyPendingPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify all pending payouts against Paystack and update their status.';

    public function handle(PayoutRepositoryInterface $payouts, PayoutAction $payoutAction): int
    {
        $pending = $payouts->pending();

        foreach ($pending as $payout) {
            $payoutAction->verify($payout);
        }

        $this->info("Verified {$pending->count()} pending payout(s).");

        return self::SUCCESS;
    }
}
