<?php

use App\Actions\RecordPurchaseAction;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Events\PurchaseMade;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\Event;

// Plain forceFill()'d instances instead of factories: factories resolve nested
// Model::factory() attributes (and PurchaseFactory's `amount` closure, which
// looks up the product) against a real database, even under make() — and this
// suite intentionally has no migrated database.
function makeUser(array $attributes = []): User
{
    return (new User)->forceFill($attributes);
}

function makeProduct(array $attributes = []): Product
{
    return (new Product)->forceFill(array_merge(['price' => 0, 'stock' => 0], $attributes));
}

function makePurchase(array $attributes = []): Purchase
{
    return (new Purchase)->forceFill($attributes);
}

beforeEach(fn () => Event::fake());

it('creates a purchase priced from the product, through the repository', function () {
    $user = makeUser(['id' => 1]);
    $product = makeProduct(['id' => 1, 'price' => 10]);
    $purchase = makePurchase(['id' => 1, 'user_id' => $user->id, 'product_id' => $product->id]);

    $products = Mockery::mock(ProductRepositoryInterface::class);
    $products->shouldReceive('decrementStock')->once()->with($product, 2)->andReturn(true);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('create')
        ->once()
        ->with($user, [
            'product_id' => $product->id,
            'quantity' => 2,
            'amount' => 20.0,
        ])
        ->andReturn($purchase);

    $result = (new RecordPurchaseAction($purchases, $products))->execute($user, $product, 2);

    expect($result)->toBe($purchase);
});

it('defaults to a quantity of one', function () {
    $user = makeUser(['id' => 1]);
    $product = makeProduct(['id' => 1, 'price' => 9.99]);
    $purchase = makePurchase(['id' => 1]);

    $products = Mockery::mock(ProductRepositoryInterface::class);
    $products->shouldReceive('decrementStock')->once()->with($product, 1)->andReturn(true);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('create')
        ->once()
        ->with($user, [
            'product_id' => $product->id,
            'quantity' => 1,
            'amount' => 9.99,
        ])
        ->andReturn($purchase);

    (new RecordPurchaseAction($purchases, $products))->execute($user, $product);
});

it('dispatches a PurchaseMade event carrying the user and the recorded purchase', function () {
    $user = makeUser(['id' => 1]);
    $product = makeProduct(['id' => 1, 'price' => 5]);
    $purchase = makePurchase(['id' => 1]);

    $products = Mockery::mock(ProductRepositoryInterface::class);
    $products->shouldReceive('decrementStock')->once()->andReturn(true);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('create')->once()->andReturn($purchase);

    (new RecordPurchaseAction($purchases, $products))->execute($user, $product);

    Event::assertDispatched(
        PurchaseMade::class,
        fn (PurchaseMade $event) => $event->user->is($user) && $event->purchase->is($purchase),
    );
});

it('throws when there is not enough stock, without touching the purchase repository', function () {
    $user = makeUser(['id' => 1]);
    $product = makeProduct(['id' => 1, 'price' => 5, 'stock' => 0]);

    $products = Mockery::mock(ProductRepositoryInterface::class);
    $products->shouldReceive('decrementStock')->once()->with($product, 3)->andReturn(false);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldNotReceive('create');

    expect(fn () => (new RecordPurchaseAction($purchases, $products))->execute($user, $product, 3))
        ->toThrow(InsufficientStockException::class);

    Event::assertNotDispatched(PurchaseMade::class);
});

it('does not dispatch PurchaseMade when the repository fails to create the purchase', function () {
    $user = makeUser(['id' => 1]);
    $product = makeProduct(['id' => 1, 'price' => 5]);

    $products = Mockery::mock(ProductRepositoryInterface::class);
    $products->shouldReceive('decrementStock')->once()->andReturn(true);

    $purchases = Mockery::mock(PurchaseRepositoryInterface::class);
    $purchases->shouldReceive('create')->once()->andThrow(new RuntimeException('db down'));

    expect(fn () => (new RecordPurchaseAction($purchases, $products))->execute($user, $product))
        ->toThrow(RuntimeException::class);

    Event::assertNotDispatched(PurchaseMade::class);
});
