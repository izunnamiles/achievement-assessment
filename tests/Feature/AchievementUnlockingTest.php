<?php

use App\Actions\RecordPurchaseAction;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;

test('a user unlocks the first purchase achievement on their first purchase', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 1]);

    app(RecordPurchaseAction::class)->execute($user, $product);

    expect($user->achievements()->where('slug', 'first-purchase')->exists())->toBeTrue()
        ->and($user->achievements()->where('slug', '5-purchases')->exists())->toBeFalse();
});

test('a user unlocks the 5 purchases achievement on their fifth purchase', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);
    $recordPurchase = app(RecordPurchaseAction::class);

    for ($i = 1; $i <= 5; $i++) {
        $recordPurchase->execute($user, $product);
    }

    expect($user->achievements()->where('slug', 'first-purchase')->exists())->toBeTrue()
        ->and($user->achievements()->where('slug', '5-purchases')->exists())->toBeTrue()
        ->and($user->achievements()->count())->toBe(2);
});

test('an achievement is never unlocked twice for the same user', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 2]);
    $recordPurchase = app(RecordPurchaseAction::class);

    $recordPurchase->execute($user, $product);
    $recordPurchase->execute($user, $product);

    expect($user->achievements()->where('slug', 'first-purchase')->count())->toBe(1);
});

test('a purchase reduces the product stock by the quantity bought', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    app(RecordPurchaseAction::class)->execute($user, $product, 3);

    expect($product->refresh()->stock)->toBe(7);
});

test('a purchase is rejected when the product does not have enough stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 2]);

    app(RecordPurchaseAction::class)->execute($user, $product, 3);
})->throws(App\Exceptions\InsufficientStockException::class);

test('an authenticated user unlocks achievements through the purchase api', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->postJson('/api/purchases', ['product_id' => $product->uuid])
        ->assertCreated();

    $response = $this->withToken($token)->getJson('/api/achievements');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.achievement.slug', 'first-purchase');
});

test('the purchase api rejects an out-of-stock product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 0]);
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->postJson('/api/purchases', ['product_id' => $product->uuid])
        ->assertStatus(422);
});

test('the purchase api 404s as JSON if the product vanishes between validation and lookup', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->mock(ProductRepositoryInterface::class)
        ->shouldReceive('findByUuid')
        ->once()
        ->andReturn(null);

    $this->withToken($token)
        ->postJson('/api/purchases', ['product_id' => $product->uuid])
        ->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('message', 'Product not found.');
});
