<?php

use App\Actions\UnlockAchievementAction;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Events\AchievementUnlocked;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => Event::fake());

it('unlocks the achievement through the repository and dispatches AchievementUnlocked', function () {
    $user = makeUser(['id' => 1]);
    $achievement = makeAchievement(['id' => 1, 'name' => 'First Purchase']);
    $userAchievement = (new UserAchievement)->forceFill(['id' => 1, 'user_id' => 1, 'achievement_id' => 1]);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('hasUnlocked')->once()->with($user, $achievement)->andReturn(false);
    $userAchievements->shouldReceive('unlock')->once()->with($user, $achievement)->andReturn($userAchievement);

    $result = (new UnlockAchievementAction($userAchievements))->execute($user, $achievement);

    expect($result)->toBe($userAchievement);

    Event::assertDispatched(
        AchievementUnlocked::class,
        fn (AchievementUnlocked $event) => $event->achievement_name === 'First Purchase' && $event->user->is($user),
    );
});

it('does nothing when the user already has the achievement', function () {
    $user = makeUser(['id' => 1]);
    $achievement = makeAchievement(['id' => 1, 'name' => 'First Purchase']);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('hasUnlocked')->once()->andReturn(true);
    $userAchievements->shouldNotReceive('unlock');

    $result = (new UnlockAchievementAction($userAchievements))->execute($user, $achievement);

    expect($result)->toBeNull();

    Event::assertNotDispatched(AchievementUnlocked::class);
});
