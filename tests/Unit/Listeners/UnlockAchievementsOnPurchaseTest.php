<?php

use App\Actions\CheckPurchaseAchievementsAction;
use App\Events\PurchaseMade;
use App\Listeners\UnlockAchievementsOnPurchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

it('delegates to CheckPurchaseAchievementsAction for the purchasing user', function () {
    $user = makeUser(['id' => 1]);
    $purchase = makePurchase(['id' => 1, 'user_id' => 1]);
    $event = new PurchaseMade($user, $purchase);

    $checkPurchaseAchievements = Mockery::mock(CheckPurchaseAchievementsAction::class);
    $checkPurchaseAchievements->shouldReceive('execute')->once()->with($user);

    (new UnlockAchievementsOnPurchase($checkPurchaseAchievements))->handle($event);
});

it('implements ShouldQueue', function () {
    expect(new UnlockAchievementsOnPurchase(Mockery::mock(CheckPurchaseAchievementsAction::class)))
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
