<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UnifiedSearchController;
use App\Http\Controllers\UserInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'marjo-tech-hub-api']));
Route::middleware('guest')->group(function (): void {
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login');
    Route::post('/auth/verify-2fa', [AuthenticatedSessionController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
    Route::get('/invitations/accept', [UserInvitationController::class, 'show'])->middleware('throttle:20,1');
    Route::post('/invitations/accept', [UserInvitationController::class, 'accept'])->middleware('throttle:10,1');
});
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', fn (Request $request) => response()->json(['user' => $request->user()->identityPayload()]));
    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::put('/auth/password', [PasswordController::class, 'update'])->middleware('throttle:5,1');
    Route::get('/profile', [ProfileController::class, 'show']); Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']); Route::get('/profile/avatar', [ProfileController::class, 'avatar']); Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar']);

    Route::get('/search', [UnifiedSearchController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/search/history', [UnifiedSearchController::class, 'history']);
    Route::delete('/search/history', [UnifiedSearchController::class, 'clearHistory'])->middleware('throttle:20,1');
    Route::get('/activity', [ActivityController::class, 'index']);
    Route::get('/activity/filter-options', [ActivityController::class, 'filterOptions']);
    Route::get('/activity/{auditEvent}', [ActivityController::class, 'show'])->whereNumber('auditEvent');

    Route::prefix('access-control')->group(function (): void {
        Route::get('/users', [AccessControlController::class, 'users']);
        Route::get('/roles', [AccessControlController::class, 'roles']);
        Route::get('/permissions', [AccessControlController::class, 'permissions']);
        Route::put('/users/{user}/roles', [AccessControlController::class, 'updateUserRoles']);
        Route::get('/invitations', [UserInvitationController::class, 'index']);
        Route::post('/invitations', [UserInvitationController::class, 'store'])->middleware('throttle:20,1');
        Route::post('/invitations/{invitation}/resend', [UserInvitationController::class, 'resend'])->middleware('throttle:10,1');
        Route::delete('/invitations/{invitation}', [UserInvitationController::class, 'revoke']);
    });

    Route::prefix('messaging')->group(function (): void {
        Route::get('/users', [MessagingController::class, 'eligibleUsers']);
        Route::get('/conversations', [MessagingController::class, 'index']);
        Route::post('/conversations', [MessagingController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/conversations/{conversation}/messages', [MessagingController::class, 'messages']);
        Route::post('/conversations/{conversation}/messages', [MessagingController::class, 'send'])->middleware('throttle:60,1');
        Route::post('/conversations/{conversation}/attachments', [MessageAttachmentController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/attachments/{attachment}/download', [MessageAttachmentController::class, 'download']);
        Route::get('/attachments/{attachment}/media', [MessageAttachmentController::class, 'media']);
        Route::put('/conversations/{conversation}/read', [MessagingController::class, 'markRead'])->middleware('throttle:120,1');
        Route::put('/conversations/{conversation}/archive', [MessagingController::class, 'archive']);
    });

    Route::apiResource('categories', CategoryController::class)->except('show'); Route::apiResource('entries', EntryController::class);
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview']); Route::get('/documents/{document}/download', [DocumentController::class, 'download']); Route::apiResource('documents', DocumentController::class);
    Route::get('/tags', [TagController::class, 'index']); Route::post('/tags', [TagController::class, 'store']); Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    Route::put('/entries/{entry}/favorite', [FavoriteController::class, 'store']); Route::delete('/entries/{entry}/favorite', [FavoriteController::class, 'destroy']);
});
