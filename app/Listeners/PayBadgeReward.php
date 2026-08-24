<?php

namespace App\Listeners;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Events\BadgeUnlocked;

class PayBadgeReward
{
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

        $this->paymentGateway->payout(
            $event->user,
            $amountInNaira,
            "Badge reward: {$event->badge_name}",
        );
    }
}
