<?php

use App\Actions\UnlockBadgeAction;
use App\Events\AchievementUnlocked;
use App\Listeners\CheckBadgesOnAchievementUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

it('delegates to UnlockBadgeAction for the user', function () {
    $user = makeUser(['id' => 1]);
    $event = new AchievementUnlocked('First Purchase', $user);

    $unlockBadge = Mockery::mock(UnlockBadgeAction::class);
    $unlockBadge->shouldReceive('unlockEligibleForUser')->once()->with($user);

    (new CheckBadgesOnAchievementUnlocked($unlockBadge))->handle($event);
});

it('implements ShouldQueue', function () {
    expect(new CheckBadgesOnAchievementUnlocked(Mockery::mock(UnlockBadgeAction::class)))
        ->toBeInstanceOf(ShouldQueue::class);
});

it('is pushed onto the queue rather than run inline when AchievementUnlocked is dispatched', function () {
    Queue::fake();

    $user = makeUser(['id' => 1]);

    event(new AchievementUnlocked('First Purchase', $user));

    Queue::assertPushed(
        CallQueuedListener::class,
        fn ($job) => $job->class === CheckBadgesOnAchievementUnlocked::class,
    );
});
