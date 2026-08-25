<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function all(): Collection
    {
        return Product::query()->orderBy('name')->get();
    }

    public function findByUuid(string $uuid): ?Product
    {
        return Product::query()->where('uuid', $uuid)->first();
    }

    public function decrementStock(Product $product, int $quantity): bool
    {
        $decremented = Product::query()
            ->whereKey($product->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        return $decremented > 0;
    }
}
