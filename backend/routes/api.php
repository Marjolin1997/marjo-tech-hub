<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'marjo-tech-hub-api']));
Route::middleware('guest')->group(function (): void {
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login');
    Route::post('/auth/verify-2fa', [AuthenticatedSessionController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
});
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', fn (Request $request) => response()->json(['user' => $request->user()->only(['id', 'name', 'first_name', 'last_name', 'username', 'job_title', 'bio', 'email']) + ['has_avatar' => (bool) ($request->user()->avatar_disk && $request->user()->avatar_path)]]));
    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::put('/auth/password', [PasswordController::class, 'update'])->middleware('throttle:5,1');
    Route::get('/profile', [ProfileController::class, 'show']); Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']); Route::get('/profile/avatar', [ProfileController::class, 'avatar']); Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar']);
    Route::apiResource('categories', CategoryController::class)->except('show'); Route::apiResource('entries', EntryController::class);
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview']); Route::get('/documents/{document}/download', [DocumentController::class, 'download']); Route::apiResource('documents', DocumentController::class);
    Route::get('/tags', [TagController::class, 'index']); Route::post('/tags', [TagController::class, 'store']); Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    Route::put('/entries/{entry}/favorite', [FavoriteController::class, 'store']); Route::delete('/entries/{entry}/favorite', [FavoriteController::class, 'destroy']);
});
