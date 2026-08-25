<?php

use App\Actions\RecordPurchaseAction;
use App\Contracts\PaymentGatewayInterface;
use App\Enums\PayoutStatus;
use App\Events\BadgeUnlocked;
use App\Models\BankAccount;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function unlockBothAchievementsFor(User $user): void
{
    $product = Product::factory()->create(['stock' => 5]);
    $recordPurchase = app(RecordPurchaseAction::class);

    for ($i = 1; $i <= 5; $i++) {
        $recordPurchase->execute($user, $product);
    }
}

test('a user unlocks a badge once they cross its achievement threshold', function () {
    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    expect($user->badges()->where('slug', 'bronze-achiever')->exists())->toBeTrue();
});

test('a badge is never unlocked twice for the same user', function () {
    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    expect($user->badges()->where('slug', 'bronze-achiever')->count())->toBe(1);
});

test('unlocking a badge dispatches BadgeUnlocked with the badge name and the user', function () {
    Event::fake([BadgeUnlocked::class]);

    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    Event::assertDispatched(
        BadgeUnlocked::class,
        fn (BadgeUnlocked $event) => $event->badge_name === 'Bronze Achiever' && $event->user->is($user),
    );
});

test('unlocking a badge triggers a payout for the configured reward amount', function () {
    $this->mock(PaymentGatewayInterface::class)
        ->shouldReceive('payout')
        ->twice()
        ->withArgs(fn (User $user, int $amount, string $reason, string $reference) => $amount === 300 && str_starts_with($reason, 'Badge reward: '))
        ->andReturn(true);

    $user = User::factory()->create();
    BankAccount::factory()->create(['user_id' => $user->id, 'paystack_recipient_code' => 'RCP_123']);

    unlockBothAchievementsFor($user);
});

test('a badge unlock creates a pending payout that stays pending without a linked bank account', function () {
    $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('payout');

    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    $payout = Payout::query()->where('user_id', $user->id)->where('reason', 'Badge reward: First Steps')->first();

    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe(PayoutStatus::Pending);
});

test('no payout is attempted when the reward amount setting is missing', function () {
    SystemSetting::query()->where('key', 'badge_reward')->delete();

    $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('payout');

    $user = User::factory()->create();

    unlockBothAchievementsFor($user);

    expect(Payout::query()->where('user_id', $user->id)->exists())->toBeFalse();
});
