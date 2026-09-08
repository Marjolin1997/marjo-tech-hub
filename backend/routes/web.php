<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Marjo Tech Hub API',
        'status' => 'running',
    ]);
});
