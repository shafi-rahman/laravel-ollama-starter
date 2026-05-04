<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

Route::middleware('api.key')->group(function () {
    Route::post('/ai/chat', [AIController::class, 'chat']);
    Route::post('/ai/stream', [AIController::class, 'stream']);
    Route::post('/ai/sse', [AIController::class, 'sse']);
});