<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/ai-test', function () {
    $response = Http::timeout(120)->post('http://localhost:11434/api/generate', [
        'model' => 'llama3',
        'prompt' => 'Explain OpenClaw architecture'
    ]);

    return $response->json();
});