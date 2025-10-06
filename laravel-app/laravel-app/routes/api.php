<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::get('/users', [UserController::class, 'index']);       
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::patch('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'service' => 'user-service',
        'timestamp' => now()
    ]);
});

