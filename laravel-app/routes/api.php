<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\ConversationController;

Route::get('/health', function () {
    try {
        $base = preg_replace('#/api/chat$#', '', config('ai.providers.ollama.url'));
        $up   = \Illuminate\Support\Facades\Http::timeout(5)->get($base)->successful();
    } catch (\Exception) {
        $up = false;
    }

    return response()->json([
        'status' => $up ? 'ok' : 'degraded',
        'ollama' => $up ? 'reachable' : 'unreachable',
        'time'   => now()->toISOString(),
    ], $up ? 200 : 503);
});

Route::middleware(['api.key', 'throttle:60,1'])->group(function () {

    // AI endpoints
    Route::post('/ai/chat',   [AIController::class, 'chat']);
    Route::post('/ai/stream', [AIController::class, 'stream']);
    Route::post('/ai/sse',    [AIController::class, 'sse']);

    // History & logs
    Route::get('/ai/conversations',              [ConversationController::class, 'index']);
    Route::get('/ai/conversations/{session_id}', [ConversationController::class, 'show']);
    Route::get('/ai/logs',                       [ConversationController::class, 'logs']);
});
