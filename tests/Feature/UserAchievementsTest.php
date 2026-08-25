<?php

use App\Actions\RecordPurchaseAction;
use App\Models\Product;
use App\Models\User;

// The 'First Purchase' (threshold 1) / '5 Purchases' (threshold 5) achievements
// and the 10-tier badge ladder ('First Steps' threshold 1, 'Bronze Achiever'
// threshold 2, 'Silver Achiever' threshold 3, ...) these tests exercise are
// all seeded directly by their respective migrations, so they already exist.

test('it lists unlocked and next-available achievements for a user with partial progress', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 1]);

    app(RecordPurchaseAction::class)->execute($user, $product);

    $response = $this->getJson("/users/{$user->uuid}/achievements");

    $response->assertOk()->assertExactJson([
        'unlocked_achievements' => ['First Purchase'],
        'next_available_achievements' => ['5 Purchases'],
        'current_badge' => 'First Steps',
        'next_badge' => 'Bronze Achiever',
        'remaining_to_unlock_next_badge' => 1,
    ]);
});

test('next_available_achievements is empty once every achievement in the group is unlocked', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);
    $recordPurchase = app(RecordPurchaseAction::class);

    for ($i = 1; $i <= 5; $i++) {
        $recordPurchase->execute($user, $product);
    }

    $response = $this->getJson("/users/{$user->uuid}/achievements");

    $response->assertOk()->assertExactJson([
        'unlocked_achievements' => ['First Purchase', '5 Purchases'],
        'next_available_achievements' => [],
        'current_badge' => 'Bronze Achiever',
        'next_badge' => 'Silver Achiever',
        'remaining_to_unlock_next_badge' => 1,
    ]);
});

test('a user with no purchases has nothing unlocked and the lowest-threshold achievement as next available', function () {
    $user = User::factory()->create();

    $response = $this->getJson("/users/{$user->uuid}/achievements");

    $response->assertOk()->assertExactJson([
        'unlocked_achievements' => [],
        'next_available_achievements' => ['First Purchase'],
        'current_badge' => '',
        'next_badge' => 'First Steps',
        'remaining_to_unlock_next_badge' => 1,
    ]);
});

test('the endpoint is publicly accessible without authentication', function () {
    $user = User::factory()->create();

    $this->getJson("/users/{$user->uuid}/achievements")->assertOk();
});

test('it resolves the user by uuid, not the internal id', function () {
    $user = User::factory()->create();

    $this->getJson("/users/{$user->id}/achievements")->assertNotFound();
});
