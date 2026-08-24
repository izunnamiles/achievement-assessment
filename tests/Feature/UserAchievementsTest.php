<?php

use App\Actions\RecordPurchaseAction;
use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\Product;
use App\Models\User;

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
});

test('it lists unlocked and next-available achievements for a user with partial progress', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 1]);

    app(RecordPurchaseAction::class)->execute($user, $product);

    $response = $this->getJson("/users/{$user->uuid}/achievements");

    $response->assertOk()->assertExactJson([
        'unlocked_achievements' => ['First Purchase'],
        'next_available_achievements' => ['5 Purchases'],
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
    ]);
});

test('a user with no purchases has nothing unlocked and the lowest-threshold achievement as next available', function () {
    $user = User::factory()->create();

    $response = $this->getJson("/users/{$user->uuid}/achievements");

    $response->assertOk()->assertExactJson([
        'unlocked_achievements' => [],
        'next_available_achievements' => ['First Purchase'],
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
