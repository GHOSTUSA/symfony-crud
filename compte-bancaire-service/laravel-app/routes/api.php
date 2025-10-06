<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

// Account routes
Route::apiResource('accounts', AccountController::class);

// Additional account routes for microservice communication
Route::get('accounts/user/{userId}', [AccountController::class, 'getByUserId']);
Route::delete('accounts/user/{userId}', [AccountController::class, 'deleteByUserId']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'service' => 'account-service',
        'timestamp' => now()
    ]);
});

