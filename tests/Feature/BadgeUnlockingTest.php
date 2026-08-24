<?php

use App\Actions\RecordPurchaseAction;
use App\Contracts\PaymentGatewayInterface;
use App\Enums\AchievementType;
use App\Events\BadgeUnlocked;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Achievement::factory()->create([
        'name' => 'First Purchase',
        'slug' => 'first-purchase',
        'type' => AchievementType::Purchases,
        'threshold' => 1,
    ]);

    Achievement::factory()->create([
        'name' => '5 Purchases',
        'slug' => '5-purchases',
        'type' => AchievementType::Purchases,
        'threshold' => 5,
    ]);

    Badge::factory()->create([
        'name' => 'Silver Achiever',
        'slug' => 'silver-achiever',
        'threshold' => 2,
    ]);

    SystemSetting::query()->create(['key' => 'badge_reward', 'value' => '300']);
});

function unlockBothAchievementsFor(User $user): void
{
    $product = Product::factory()->create();
    $recordPurchase = app(RecordPurchaseAction::class);

    for ($i = 1; $i <= 5; $i++) {
        $recordPurchase->execute($user, $product);
    }
}

test('a user unlocks a badge once they cross its achievement threshold', function () {
    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    expect($user->badges()->where('slug', 'silver-achiever')->exists())->toBeTrue();
});

test('a badge is never unlocked twice for the same user', function () {
    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    expect($user->badges()->where('slug', 'silver-achiever')->count())->toBe(1);
});

test('unlocking a badge dispatches BadgeUnlocked with the badge name and the user', function () {
    Event::fake([BadgeUnlocked::class]);

    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    Event::assertDispatched(
        BadgeUnlocked::class,
        fn (BadgeUnlocked $event) => $event->badge_name === 'Silver Achiever' && $event->user->is($user),
    );
});

test('unlocking a badge triggers a payout for the configured reward amount', function () {
    $this->mock(PaymentGatewayInterface::class)
        ->shouldReceive('payout')
        ->once()
        ->withArgs(fn (User $user, int $amount, string $reason) => $amount === 300 && str_contains($reason, 'Silver Achiever'))
        ->andReturn(true);

    $user = User::factory()->create();

    unlockBothAchievementsFor($user);
});

test('no payout is attempted when the reward amount setting is missing', function () {
    SystemSetting::query()->where('key', 'badge_reward')->delete();

    $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('payout');

    $user = User::factory()->create();

    unlockBothAchievementsFor($user);
});
