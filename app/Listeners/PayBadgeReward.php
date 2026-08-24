<?php

namespace App\Listeners;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Events\BadgeUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class PayBadgeReward implements ShouldQueue
{
    use InteractsWithQueue;

    private const SETTING_KEY = 'badge_reward';

    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly SystemSettingRepositoryInterface $settings,
    ) {}

    public function handle(BadgeUnlocked $event): void
    {
        $amountInNaira = (int) $this->settings->get(self::SETTING_KEY, 0);

        if ($amountInNaira <= 0) {
            return;
        }

        // Guards against overlapping deliveries of the same badge reward
        // being paid out concurrently (e.g. a job redelivered after its queue
        // visibility timeout expires mid-payout, while the original attempt
        // is still running). This does not protect against a retry that runs
        // after an earlier attempt already completed successfully - that
        // needs a persisted "already paid" record, which doesn't exist yet.
        Cache::lock("pay-badge-reward:{$event->user->id}:{$event->badge_name}", 30)
            ->block(5, fn () => $this->paymentGateway->payout(
                $event->user,
                $amountInNaira,
                "Badge reward: {$event->badge_name}",
            ));
    }
}
