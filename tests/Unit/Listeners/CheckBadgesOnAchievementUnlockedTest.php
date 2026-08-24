<?php

use App\Actions\CheckBadgesAction;
use App\Events\AchievementUnlocked;
use App\Listeners\CheckBadgesOnAchievementUnlocked;

it('delegates to CheckBadgesAction for the user', function () {
    $user = makeUser(['id' => 1]);
    $event = new AchievementUnlocked('First Purchase', $user);

    $checkBadges = Mockery::mock(CheckBadgesAction::class);
    $checkBadges->shouldReceive('execute')->once()->with($user);

    (new CheckBadgesOnAchievementUnlocked($checkBadges))->handle($event);
});
