<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(ProductRepositoryInterface $products): JsonResponse
    {
        return response()->json([
            'message' => 'Products retrieved successfully.',
            'data' => $products->all(),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'message' => 'Product retrieved successfully.',
            'data' => $product,
        ]);
    }
}
