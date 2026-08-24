<?php

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Listeners\PayBadgeReward;

it('pays out the configured reward amount for the badge', function () {
    $user = makeUser(['id' => 1]);
    $event = new BadgeUnlocked('Silver Achiever', $user);

    $settings = Mockery::mock(SystemSettingRepositoryInterface::class);
    $settings->shouldReceive('get')->once()->with('badge_reward', 0)->andReturn(300);

    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('payout')
        ->once()
        ->with($user, 300, 'Badge reward: Silver Achiever')
        ->andReturn(true);

    (new PayBadgeReward($gateway, $settings))->handle($event);
});

it('does not attempt a payout when no reward amount is configured', function () {
    $user = makeUser(['id' => 1]);
    $event = new BadgeUnlocked('Silver Achiever', $user);

    $settings = Mockery::mock(SystemSettingRepositoryInterface::class);
    $settings->shouldReceive('get')->once()->andReturn(0);

    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldNotReceive('payout');

    (new PayBadgeReward($gateway, $settings))->handle($event);
});
