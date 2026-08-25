<?php

use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

// Public: Paystack calls this directly, with no JWT of ours to send. The
// signature check inside the controller is what authenticates the request.
Route::post('/paystack/webhook', PaystackWebhookController::class);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::post('/bank-account', [BankAccountController::class, 'store']);
});
