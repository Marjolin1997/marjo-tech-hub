<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'marjo-tech-hub-api']));

Route::middleware('guest')->group(function (): void {
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', fn (Request $request) => response()->json(['user' => $request->user()->only(['id', 'name', 'email'])]));
    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::apiResource('categories', CategoryController::class)->except('show');
    Route::apiResource('entries', EntryController::class);
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    Route::put('/entries/{entry}/favorite', [FavoriteController::class, 'store']);
    Route::delete('/entries/{entry}/favorite', [FavoriteController::class, 'destroy']);
});
