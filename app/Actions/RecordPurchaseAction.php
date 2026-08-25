<?php

namespace App\Actions;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Enums\AuditType;
use App\Events\PurchaseMade;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;

/**
 * Records a purchase, decrementing stock and dispatching PurchaseMade.
 */
class RecordPurchaseAction
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchases,
        private readonly ProductRepositoryInterface $products,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function execute(User $user, Product $product, int $quantity = 1): Purchase
    {
        if (! $this->products->decrementStock($product, $quantity)) {
            throw new InsufficientStockException($product, $quantity);
        }

        $purchase = $this->purchases->create($user, [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'amount' => $product->price * $quantity,
        ]);

        event(new PurchaseMade($user, $purchase));

        $this->auditLogs->record(
            $user,
            AuditType::Purchase,
            "Purchased {$quantity} x {$product->name}",
            ['product_id' => $product->id, 'quantity' => $quantity, 'amount' => $purchase->amount],
        );

        return $purchase;
    }
}
