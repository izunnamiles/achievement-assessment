<?php

use App\Actions\UnlockAchievementAction;
use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Enums\AchievementType;
use App\Enums\AuditType;
use App\Events\AchievementUnlocked;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => Event::fake());

it('unlocks the achievement through the repository and dispatches AchievementUnlocked', function () {
    $user = makeUser(['id' => 1]);
    $achievement = makeAchievement(['id' => 1, 'name' => 'First Purchase', 'slug' => 'first-purchase']);
    $userAchievement = (new UserAchievement)->forceFill(['id' => 1, 'user_id' => 1, 'achievement_id' => 1]);

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('hasUnlocked')->once()->with($user, $achievement)->andReturn(false);
    $userAchievements->shouldReceive('unlock')->once()->with($user, $achievement)->andReturn($userAchievement);

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldReceive('record')->once()->with(
        $user,
        AuditType::AchievementUnlocked,
        'Unlocked achievement: First Purchase',
        ['achievement_id' => 1, 'slug' => 'first-purchase'],
    );

    $action = new UnlockAchievementAction(
        Mockery::mock(PurchaseRepositoryInterface::class),
        Mockery::mock(AchievementRepositoryInterface::class),
        $userAchievements,
        $auditLogs,
    );

    $result = $action->unlock($user, $achievement);

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

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldNotReceive('record');

    $action = new UnlockAchievementAction(
        Mockery::mock(PurchaseRepositoryInterface::class),
        Mockery::mock(AchievementRepositoryInterface::class),
        $userAchievements,
        $auditLogs,
    );

    $result = $action->unlock($user, $achievement);

    expect($result)->toBeNull();

    Event::assertNotDispatched(AchievementUnlocked::class);
});

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

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldReceive('hasUnlocked')->twice()->andReturn(true);

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldNotReceive('record');

    (new UnlockAchievementAction($purchases, $achievements, $userAchievements, $auditLogs))->unlockEligibleForUser($user);
});

it('unlocks no achievements when the purchase count reaches no threshold', function () {
    $user = makeUser(['id' => 1]);
    $first = makeAchievement(['id' => 1, 'threshold' => 1]);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('countForUser')->once()->andReturn(0);

    $achievements = Mockery::mock(AchievementRepositoryInterface::class);
    $achievements->shouldReceive('allByType')->once()->andReturn(new Collection([$first]));

    $userAchievements = Mockery::mock(UserAchievementRepositoryInterface::class);
    $userAchievements->shouldNotReceive('hasUnlocked');
    $userAchievements->shouldNotReceive('unlock');

    $auditLogs = Mockery::mock(AuditLogRepositoryInterface::class);
    $auditLogs->shouldNotReceive('record');

    (new UnlockAchievementAction($purchases, $achievements, $userAchievements, $auditLogs))->unlockEligibleForUser($user);
});
