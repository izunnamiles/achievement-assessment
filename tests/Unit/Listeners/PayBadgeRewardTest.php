<?php

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Listeners\PayBadgeReward;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

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

it('implements ShouldQueue', function () {
    $listener = new PayBadgeReward(
        Mockery::mock(PaymentGatewayInterface::class),
        Mockery::mock(SystemSettingRepositoryInterface::class),
    );

    expect($listener)->toBeInstanceOf(ShouldQueue::class);
});

it('is pushed onto the queue rather than run inline when BadgeUnlocked is dispatched', function () {
    Queue::fake();

    $user = makeUser(['id' => 1]);

    event(new BadgeUnlocked('Silver Achiever', $user));

    Queue::assertPushed(
        CallQueuedListener::class,
        fn ($job) => $job->class === PayBadgeReward::class,
    );
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
