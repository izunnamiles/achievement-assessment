<?php

namespace App\Contracts\Repositories;

use App\Models\Product;

interface ProductRepositoryInterface
{
    public function findByUuid(string $uuid): ?Product;

    /**
     * Atomically reduce a product's stock, only if enough is available.
     *
     * @return bool  true if stock was decremented, false if there wasn't enough available.
     */
    public function decrementStock(Product $product, int $quantity): bool;
}
