<?php

use App\Actions\PayoutAction;
use App\Contracts\Repositories\PayoutRepositoryInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Enums\PayoutStatus;
use App\Events\BadgeUnlocked;
use App\Listeners\PayBadgeReward;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

it('creates a pending payout and attempts it', function () {
    $user = makeUser(['id' => 1]);
    $event = new BadgeUnlocked('Silver Achiever', $user);
    $payout = makePayout(['id' => 1, 'user_id' => 1, 'status' => PayoutStatus::Pending]);

    $settings = Mockery::mock(SystemSettingRepositoryInterface::class);
    $settings->shouldReceive('get')->once()->with('badge_reward', 0)->andReturn(300);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('firstOrCreatePending')
        ->once()
        ->with($user, 300, 'Badge reward: Silver Achiever')
        ->andReturn($payout);

    $payoutAction = Mockery::mock(PayoutAction::class);
    $payoutAction->shouldReceive('attempt')->once()->with($payout);

    (new PayBadgeReward($payouts, $settings, $payoutAction))->handle($event);
});

it('does not attempt an already-resolved payout', function () {
    $user = makeUser(['id' => 1]);
    $event = new BadgeUnlocked('Silver Achiever', $user);
    $payout = makePayout(['id' => 1, 'user_id' => 1, 'status' => PayoutStatus::Paid]);

    $settings = Mockery::mock(SystemSettingRepositoryInterface::class);
    $settings->shouldReceive('get')->once()->andReturn(300);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('firstOrCreatePending')->once()->andReturn($payout);

    $payoutAction = Mockery::mock(PayoutAction::class);
    $payoutAction->shouldNotReceive('attempt');

    (new PayBadgeReward($payouts, $settings, $payoutAction))->handle($event);
});

it('implements ShouldQueue', function () {
    $listener = new PayBadgeReward(
        Mockery::mock(PayoutRepositoryInterface::class),
        Mockery::mock(SystemSettingRepositoryInterface::class),
        Mockery::mock(PayoutAction::class),
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

it('does not create a payout when no reward amount is configured', function () {
    $user = makeUser(['id' => 1]);
    $event = new BadgeUnlocked('Silver Achiever', $user);

    $settings = Mockery::mock(SystemSettingRepositoryInterface::class);
    $settings->shouldReceive('get')->once()->andReturn(0);

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldNotReceive('firstOrCreatePending');

    $payoutAction = Mockery::mock(PayoutAction::class);
    $payoutAction->shouldNotReceive('attempt');

    (new PayBadgeReward($payouts, $settings, $payoutAction))->handle($event);
});
