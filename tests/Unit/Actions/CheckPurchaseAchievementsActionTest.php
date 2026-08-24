<?php

use App\Actions\CheckPurchaseAchievementsAction;
use App\Actions\UnlockAchievementAction;
use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Enums\AchievementType;
use Illuminate\Support\Collection;

it('unlocks every achievement whose threshold the purchase count has reached', function () {
    $user = makeUser(['id' => 1]);
    $first = makeAchievement(['id' => 1, 'threshold' => 1]);
    $five = makeAchievement(['id' => 2, 'threshold' => 5]);
    $ten = makeAchievement(['id' => 3, 'threshold' => 10]);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('countForUser')->once()->with($user)->andReturn(5);

    $achievements = Mockery::mock(AchievementRepositoryInterface::class);
    $achievements->shouldReceive('allByType')
        ->once()
        ->with(AchievementType::Purchases)
        ->andReturn(new Collection([$first, $five, $ten]));

    $unlockAchievement = Mockery::mock(UnlockAchievementAction::class);
    $unlockAchievement->shouldReceive('execute')->once()->with($user, $first);
    $unlockAchievement->shouldReceive('execute')->once()->with($user, $five);

    (new CheckPurchaseAchievementsAction($purchases, $achievements, $unlockAchievement))->execute($user);
});

it('unlocks no achievements when the purchase count reaches no threshold', function () {
    $user = makeUser(['id' => 1]);
    $first = makeAchievement(['id' => 1, 'threshold' => 1]);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('countForUser')->once()->andReturn(0);

    $achievements = Mockery::mock(AchievementRepositoryInterface::class);
    $achievements->shouldReceive('allByType')->once()->andReturn(new Collection([$first]));

    $unlockAchievement = Mockery::mock(UnlockAchievementAction::class);
    $unlockAchievement->shouldNotReceive('execute');

    (new CheckPurchaseAchievementsAction($purchases, $achievements, $unlockAchievement))->execute($user);
});
