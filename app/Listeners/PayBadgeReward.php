<?php

namespace App\Listeners;

use App\Actions\PayoutAction;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Enums\PayoutStatus;
use App\Events\BadgeUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PayBadgeReward implements ShouldQueue
{
    use InteractsWithQueue;

    private const SETTING_KEY = 'badge_reward';

    public function __construct(
        private readonly PayoutRepositoryInterface $payouts,
        private readonly SystemSettingRepositoryInterface $settings,
        private readonly PayoutAction $payoutAction,
    ) {}

    public function handle(BadgeUnlocked $event): void
    {
        $amountInNaira = (int) $this->settings->get(self::SETTING_KEY, 0);

        if ($amountInNaira <= 0) {
            return;
        }

        // firstOrCreatePending is keyed on (user_id, reason), so a
        // retried/redelivered job finds the existing payout instead of
        // creating (and paying) a second one.
        $payout = $this->payouts->firstOrCreatePending(
            $event->user,
            $amountInNaira,
            "Badge reward: {$event->badge_name}",
        );

        if ($payout->status === PayoutStatus::Pending) {
            $this->payoutAction->attempt($payout);
        }
    }
}
