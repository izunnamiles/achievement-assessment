<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(ProductRepositoryInterface $products): JsonResponse
    {
        return response()->json([
            'message' => 'Products retrieved successfully.',
            'data' => ProductResource::collection($products->all()),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'message' => 'Product retrieved successfully.',
            'data' => new ProductResource($product),
        ]);
    }
}
