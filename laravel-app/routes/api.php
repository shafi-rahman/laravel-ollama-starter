<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

Route::get('/health', function () {
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(5)
            ->get(str_replace('/api/chat', '', config('ai.providers.ollama.url')));
        $ollamaUp = $response->successful();
    } catch (\Exception) {
        $ollamaUp = false;
    }

    return response()->json([
        'status'  => $ollamaUp ? 'ok' : 'degraded',
        'ollama'  => $ollamaUp ? 'reachable' : 'unreachable',
        'time'    => now()->toISOString(),
    ], $ollamaUp ? 200 : 503);
});

Route::middleware(['api.key', 'throttle:60,1'])->group(function () {
    Route::post('/ai/chat', [AIController::class, 'chat']);
    Route::post('/ai/stream', [AIController::class, 'stream']);
    Route::post('/ai/sse', [AIController::class, 'sse']);
});