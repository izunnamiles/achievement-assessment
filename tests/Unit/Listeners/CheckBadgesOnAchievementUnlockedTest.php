<?php

use App\Actions\CheckBadgesAction;
use App\Events\AchievementUnlocked;
use App\Listeners\CheckBadgesOnAchievementUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

it('delegates to CheckBadgesAction for the user', function () {
    $user = makeUser(['id' => 1]);
    $event = new AchievementUnlocked('First Purchase', $user);

    $checkBadges = Mockery::mock(CheckBadgesAction::class);
    $checkBadges->shouldReceive('execute')->once()->with($user);

    (new CheckBadgesOnAchievementUnlocked($checkBadges))->handle($event);
});

it('implements ShouldQueue', function () {
    expect(new CheckBadgesOnAchievementUnlocked(Mockery::mock(CheckBadgesAction::class)))
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
