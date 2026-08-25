<?php

use App\Models\Product;
use App\Models\User;

test('an authenticated user can list products, ordered by name', function () {
    // Named to sort first/last regardless of whatever ProductSeeder's own
    // baseline products (this test runs with the database seeded) are called.
    Product::factory()->create(['name' => 'Zzz Test Widget', 'price' => 10, 'stock' => 5]);
    Product::factory()->create(['name' => 'Aaa Test Gadget', 'price' => 20, 'stock' => 3]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $response = $this->withToken($token)->getJson('/api/products');
    $names = collect($response->json('data'))->pluck('name');

    $response->assertOk();
    expect($names->search('Aaa Test Gadget'))->toBeLessThan($names->search('Zzz Test Widget'));
});

test('an authenticated user can view a single product by its uuid', function () {
    $product = Product::factory()->create(['name' => 'Widget', 'price' => 10, 'stock' => 5]);

    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->getJson("/api/products/{$product->uuid}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Widget')
        ->assertJsonPath('data.uuid', $product->uuid);
});

test('a nonexistent product uuid returns a 404', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->getJson('/api/products/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

test('the product endpoints require authentication', function () {
    $product = Product::factory()->create();

    $this->getJson('/api/products')->assertUnauthorized();
    $this->getJson("/api/products/{$product->uuid}")->assertUnauthorized();
});
