<?php

use App\Actions\UnlockAchievementAction;
use App\Events\PurchaseMade;
use App\Listeners\UnlockAchievementsOnPurchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

it('delegates to UnlockAchievementAction for the purchasing user', function () {
    $user = makeUser(['id' => 1]);
    $purchase = makePurchase(['id' => 1, 'user_id' => 1]);
    $event = new PurchaseMade($user, $purchase);

    $unlockAchievement = Mockery::mock(UnlockAchievementAction::class);
    $unlockAchievement->shouldReceive('unlockEligibleForUser')->once()->with($user);

    (new UnlockAchievementsOnPurchase($unlockAchievement))->handle($event);
});

it('implements ShouldQueue', function () {
    expect(new UnlockAchievementsOnPurchase(Mockery::mock(UnlockAchievementAction::class)))
        ->toBeInstanceOf(ShouldQueue::class);
});

it('is pushed onto the queue rather than run inline when PurchaseMade is dispatched', function () {
    Queue::fake();

    $user = makeUser(['id' => 1]);
    $purchase = makePurchase(['id' => 1, 'user_id' => 1]);

    event(new PurchaseMade($user, $purchase));

    Queue::assertPushed(
        CallQueuedListener::class,
        fn ($job) => $job->class === UnlockAchievementsOnPurchase::class,
    );
});
