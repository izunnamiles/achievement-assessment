<?php

use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::post('/bank-account', [BankAccountController::class, 'store']);
});
