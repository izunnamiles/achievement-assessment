<?php

use App\Actions\CheckBadgesAction;
use App\Actions\UnlockBadgeAction;
use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use Illuminate\Support\Collection;

it('unlocks every badge whose threshold the achievement count has reached', function () {
    $user = makeUser(['id' => 1]);
    $bronze = makeBadge(['id' => 1, 'threshold' => 1]);
    $silver = makeBadge(['id' => 2, 'threshold' => 2]);
    $gold = makeBadge(['id' => 3, 'threshold' => 5]);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('countForUser')->once()->with($user)->andReturn(2);

    $badges = Mockery::mock(BadgeRepositoryInterface::class);
    $badges->shouldReceive('all')->once()->andReturn(new Collection([$bronze, $silver, $gold]));

    $unlockBadge = Mockery::mock(UnlockBadgeAction::class);
    $unlockBadge->shouldReceive('execute')->once()->with($user, $bronze);
    $unlockBadge->shouldReceive('execute')->once()->with($user, $silver);

    (new CheckBadgesAction($userAchievements, $badges, $unlockBadge))->execute($user);
});

it('unlocks no badges when the achievement count reaches no threshold', function () {
    $user = makeUser(['id' => 1]);
    $silver = makeBadge(['id' => 1, 'threshold' => 2]);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('countForUser')->once()->andReturn(0);

    $badges = Mockery::mock(BadgeRepositoryInterface::class);
    $badges->shouldReceive('all')->once()->andReturn(new Collection([$silver]));

    $unlockBadge = Mockery::mock(UnlockBadgeAction::class);
    $unlockBadge->shouldNotReceive('execute');

    (new CheckBadgesAction($userAchievements, $badges, $unlockBadge))->execute($user);
});
