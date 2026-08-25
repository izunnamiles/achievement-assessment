<?php

use App\Actions\RecordPurchaseAction;
use App\Contracts\PaymentGatewayInterface;
use App\Enums\AuditType;
use App\Enums\PayoutStatus;
use App\Events\BadgeUnlocked;
use App\Models\AuditLog;
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

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('a user unlocks a badge once they cross its achievement threshold', function () {
    unlockBothAchievementsFor($this->user);

    expect($this->user->badges()->where('slug', 'bronze-achiever')->exists())->toBeTrue();

    expect(AuditLog::query()->where('user_id', $this->user->id)->where('type', AuditType::BadgeUnlocked)->exists())->toBeTrue();
});

test('a badge is never unlocked twice for the same user', function () {
    unlockBothAchievementsFor($this->user);

    expect($this->user->badges()->where('slug', 'bronze-achiever')->count())->toBe(1);
});

test('unlocking a badge dispatches BadgeUnlocked with the badge name and the user', function () {
    Event::fake([BadgeUnlocked::class]);

    unlockBothAchievementsFor($this->user);

    Event::assertDispatched(
        BadgeUnlocked::class,
        fn (BadgeUnlocked $event) => $event->badge_name === 'Bronze Achiever' && $event->user->is($this->user),
    );
});

test('unlocking a badge triggers a payout for the configured reward amount', function () {
    $this->mock(PaymentGatewayInterface::class)
        ->shouldReceive('payout')
        ->twice()
        ->withArgs(fn (User $user, int $amount, string $reason, string $reference) => $amount === 300 && str_starts_with($reason, 'Badge reward: '))
        ->andReturn(true);

    BankAccount::factory()->create(['user_id' => $this->user->id, 'paystack_recipient_code' => 'RCP_123']);

    unlockBothAchievementsFor($this->user);
});

test('a badge unlock creates a pending payout that stays pending without a linked bank account', function () {
    $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('payout');

    unlockBothAchievementsFor($this->user);

    $payout = Payout::query()->where('user_id', $this->user->id)->where('reason', 'Badge reward: First Steps')->first();

    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe(PayoutStatus::Pending);
});

test('no payout is attempted when the reward amount setting is missing', function () {
    SystemSetting::query()->where('key', 'badge_reward')->delete();

    $this->mock(PaymentGatewayInterface::class)->shouldNotReceive('payout');

    unlockBothAchievementsFor($this->user);

    expect(Payout::query()->where('user_id', $this->user->id)->exists())->toBeFalse();
});
