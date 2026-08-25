<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordPurchaseAction;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function store(
        Request $request,
        ProductRepositoryInterface $products,
        RecordPurchaseAction $recordPurchase,
    ): JsonResponse {
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'exists:products,uuid'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $product = $products->findByUuid($validated['product_id']);

        abort_if($product === null, 404, 'Product not found.');

        $purchase = $recordPurchase->execute($request->user(), $product, $validated['quantity'] ?? 1);

        return response()->json([
            'message' => 'Purchase recorded successfully.',
            'data' => new PurchaseResource($purchase->load('product')),
        ], 201);
    }
}
