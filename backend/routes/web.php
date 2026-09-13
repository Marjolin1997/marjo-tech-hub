<?php

use App\Http\Controllers\NetworkSpeedTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Marjo Tech Hub API',
        'status' => 'running',
    ]);
});

Route::prefix('network-test')->middleware('throttle:30,1')->group(function () {
    Route::get('/ping', [NetworkSpeedTestController::class, 'ping']);
    Route::get('/download', [NetworkSpeedTestController::class, 'download']);
    Route::post('/upload', [NetworkSpeedTestController::class, 'upload']);
});
