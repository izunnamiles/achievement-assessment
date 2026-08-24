<?php

use App\Actions\CheckPurchaseAchievementsAction;
use App\Events\PurchaseMade;
use App\Listeners\UnlockAchievementsOnPurchase;

it('delegates to CheckPurchaseAchievementsAction for the purchasing user', function () {
    $user = makeUser(['id' => 1]);
    $purchase = makePurchase(['id' => 1, 'user_id' => 1]);
    $event = new PurchaseMade($user, $purchase);

    $checkPurchaseAchievements = Mockery::mock(CheckPurchaseAchievementsAction::class);
    $checkPurchaseAchievements->shouldReceive('execute')->once()->with($user);

    (new UnlockAchievementsOnPurchase($checkPurchaseAchievements))->handle($event);
});
