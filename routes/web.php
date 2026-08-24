<?php

use App\Http\Controllers\UserAchievementsController;
use Illuminate\Support\Facades\Route;

Route::get('/users/{user}/achievements', [UserAchievementsController::class, 'show']);
