<?php

use App\Actions\RecordPurchaseAction;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\AuditType;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->product = fn (int $stock = 0) => Product::factory()->create(['stock' => $stock]);
});

test('a user unlocks the first purchase achievement on their first purchase', function () {
    $product = ($this->product)(1);

    app(RecordPurchaseAction::class)->execute($this->user, $product);

    expect($this->user->achievements()->where('slug', 'first-purchase')->exists())->toBeTrue()
        ->and($this->user->achievements()->where('slug', '5-purchases')->exists())->toBeFalse();

    expect(AuditLog::query()->where('user_id', $this->user->id)->where('type', AuditType::Purchase)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('user_id', $this->user->id)->where('type', AuditType::AchievementUnlocked)->exists())->toBeTrue();
});

test('a user unlocks the 5 purchases achievement on their fifth purchase', function () {
    $product = ($this->product)(5);
    $recordPurchase = app(RecordPurchaseAction::class);

    for ($i = 1; $i <= 5; $i++) {
        $recordPurchase->execute($this->user, $product);
    }

    expect($this->user->achievements()->where('slug', 'first-purchase')->exists())->toBeTrue()
        ->and($this->user->achievements()->where('slug', '5-purchases')->exists())->toBeTrue()
        ->and($this->user->achievements()->count())->toBe(2);
});

test('an achievement is never unlocked twice for the same user', function () {
    $product = ($this->product)(2);
    $recordPurchase = app(RecordPurchaseAction::class);

    $recordPurchase->execute($this->user, $product);
    $recordPurchase->execute($this->user, $product);

    expect($this->user->achievements()->where('slug', 'first-purchase')->count())->toBe(1);
});

test('a purchase reduces the product stock by the quantity bought', function () {
    $product = ($this->product)(10);

    app(RecordPurchaseAction::class)->execute($this->user, $product, 3);

    expect($product->refresh()->stock)->toBe(7);
});

test('a purchase is rejected when the product does not have enough stock', function () {
    $product = ($this->product)(2);

    app(RecordPurchaseAction::class)->execute($this->user, $product, 3);
})->throws(App\Exceptions\InsufficientStockException::class);

test('an authenticated user unlocks achievements through the purchase api', function () {
    $product = ($this->product)(10);
    $token = auth('api')->login($this->user);

    $this->withToken($token)
        ->postJson('/api/purchases', ['product_id' => $product->uuid])
        ->assertCreated();

    $response = $this->withToken($token)->getJson('/api/achievements');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.achievement.slug', 'first-purchase');
});

test('the purchase api rejects an out-of-stock product', function () {
    $product = ($this->product)(0);
    $token = auth('api')->login($this->user);

    $this->withToken($token)
        ->postJson('/api/purchases', ['product_id' => $product->uuid])
        ->assertStatus(422);
});

test('the purchase api 404s as JSON if the product vanishes between validation and lookup', function () {
    $product = ($this->product)();
    $token = auth('api')->login($this->user);

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
