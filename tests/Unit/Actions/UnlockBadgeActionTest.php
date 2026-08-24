<?php

use App\Actions\UnlockBadgeAction;
use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Events\BadgeUnlocked;
use App\Models\UserBadge;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => Event::fake());

it('unlocks the badge through the repository and dispatches BadgeUnlocked', function () {
    $user = makeUser(['id' => 1]);
    $badge = makeBadge(['id' => 1, 'name' => 'Silver Achiever']);
    $userBadge = (new UserBadge)->forceFill(['id' => 1, 'user_id' => 1, 'badge_id' => 1]);

    $userBadges = Mockery::mock(UserBadgeRepositoryInterface::class);
    $userBadges->shouldReceive('hasUnlocked')->once()->with($user, $badge)->andReturn(false);
    $userBadges->shouldReceive('unlock')->once()->with($user, $badge)->andReturn($userBadge);

    $result = (new UnlockBadgeAction($userBadges))->execute($user, $badge);

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

    $result = (new UnlockBadgeAction($userBadges))->execute($user, $badge);

    expect($result)->toBeNull();

    Event::assertNotDispatched(BadgeUnlocked::class);
});
