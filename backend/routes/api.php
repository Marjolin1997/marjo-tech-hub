<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'marjo-tech-hub-api',
]));

Route::middleware('guest')->group(function (): void {
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', fn (Request $request) => response()->json([
        'user' => $request->user()->only(['id', 'name', 'email']),
    ]));

    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);
});
