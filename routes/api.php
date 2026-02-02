<?php

use App\Http\Controllers\api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleCheck;
use App\Http\Controllers\api\TrekController;
use App\Http\Controllers\api\BlogsController;
use App\Http\Controllers\api\ReviewController;
use App\Http\Controllers\api\ActivityController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');



Route::apiResource('treks', TrekController::class);

Route::apiResource('activities', ActivityController::class);

Route::prefix('blogs')->middleware(['auth:sanctum', 'role'])->group(function () {
    Route::post('/', [BlogsController::class, 'store']);
    Route::post('/{id}', [BlogsController::class, 'update']); // Use POST for file uploads
    Route::delete('/{id}', [BlogsController::class, 'destroy']);
    Route::get('/total', [BlogsController::class, 'getTotalBlogs']);
});

Route::group(['prefix' => 'blogs'], function () {
    Route::get('/{id}', [BlogsController::class, 'show']);
    Route::get('/', [BlogsController::class, 'index']);
});

// Public review submission (no auth required) - rate limited
Route::prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'submitReview'])->middleware('throttle:3,1'); // 3 reviews per minute
    Route::get('/publishable', [ReviewController::class, 'getPublishableReviews']);
    Route::get('/latest', [ReviewController::class, 'getFourReviews']);
    Route::get('/stats', [ReviewController::class, 'getPositiveAndNegativeReviewsCount']);
    Route::get('/', [ReviewController::class, 'getReviews']);
});

// Protected review management routes (require authentication)
Route::prefix('reviews')->middleware(['auth:sanctum', 'role'])->group(function () {
    Route::put('/{id}/approve', [ReviewController::class, 'approveReview']);
    Route::delete('/{id}', [ReviewController::class, 'delete']);
});

