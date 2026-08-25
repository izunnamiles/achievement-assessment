<?php

use App\Actions\UnlockBadgeAction;
use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Models\UserBadge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => Event::fake());

it('unlocks the badge through the repository and dispatches BadgeUnlocked', function () {
    $user = makeUser(['id' => 1]);
    $badge = makeBadge(['id' => 1, 'name' => 'Silver Achiever']);
    $userBadge = (new UserBadge)->forceFill(['id' => 1, 'user_id' => 1, 'badge_id' => 1]);

    $userBadges = Mockery::mock(UserBadgeRepositoryInterface::class);
    $userBadges->shouldReceive('hasUnlocked')->once()->with($user, $badge)->andReturn(false);
    $userBadges->shouldReceive('unlock')->once()->with($user, $badge)->andReturn($userBadge);

    $action = new UnlockBadgeAction(
        Mockery::mock(UserAchievementRepositoryInterface::class),
        Mockery::mock(BadgeRepositoryInterface::class),
        $userBadges,
    );

    $result = $action->unlock($user, $badge);

    expect($result)->toBe($userBadge);

    Event::assertDispatched(
        BadgeUnlocked::class,
        fn (BadgeUnlocked $event) => $event->badge_name === 'Silver Achiever' && $event->user->is($user),
    );
});

it('does nothing when the user already has the badge', function () {
    $user = makeUser(['id' => 1]);
    $badge = makeBadge(['id' => 1, 'name' => 'Silver Achiever']);

    $userBadges = Mockery::mock(UserBadgeRepositoryInterface::class);
    $userBadges->shouldReceive('hasUnlocked')->once()->andReturn(true);
    $userBadges->shouldNotReceive('unlock');

    $action = new UnlockBadgeAction(
        Mockery::mock(UserAchievementRepositoryInterface::class),
        Mockery::mock(BadgeRepositoryInterface::class),
        $userBadges,
    );

    $result = $action->unlock($user, $badge);

    expect($result)->toBeNull();

    Event::assertNotDispatched(BadgeUnlocked::class);
});

it('unlocks every badge whose threshold the achievement count has reached', function () {
    $user = makeUser(['id' => 1]);
    $bronze = makeBadge(['id' => 1, 'threshold' => 1]);
    $silver = makeBadge(['id' => 2, 'threshold' => 2]);
    $gold = makeBadge(['id' => 3, 'threshold' => 5]);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('countForUser')->once()->with($user)->andReturn(2);

    $badges = Mockery::mock(BadgeRepositoryInterface::class);
    $badges->shouldReceive('all')->once()->andReturn(new Collection([$bronze, $silver, $gold]));

    $userBadges = Mockery::mock(UserBadgeRepositoryInterface::class);
    $userBadges->shouldReceive('hasUnlocked')->twice()->andReturn(true);

    (new UnlockBadgeAction($userAchievements, $badges, $userBadges))->unlockEligibleForUser($user);
});

it('unlocks no badges when the achievement count reaches no threshold', function () {
    $user = makeUser(['id' => 1]);
    $silver = makeBadge(['id' => 1, 'threshold' => 2]);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('countForUser')->once()->andReturn(0);

    $badges = Mockery::mock(BadgeRepositoryInterface::class);
    $badges->shouldReceive('all')->once()->andReturn(new Collection([$silver]));

    $userBadges = Mockery::mock(UserBadgeRepositoryInterface::class);
    $userBadges->shouldNotReceive('hasUnlocked');
    $userBadges->shouldNotReceive('unlock');

    (new UnlockBadgeAction($userAchievements, $badges, $userBadges))->unlockEligibleForUser($user);
});
