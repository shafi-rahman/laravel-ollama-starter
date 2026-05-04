<?php
namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIProvider;

class OllamaProvider implements AIProvider
{

    public function generate(string $prompt, string $model): array
    {
        set_time_limit(600);
        return Http::timeout(600)->post(
            config('ai.providers.ollama.url'),
            [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false
            ]
        )->json();
    }

    // public function generate(string $prompt, string $model): array
    // {
    //     return Http::post('http://127.0.0.1:11434/api/generate', [
    //         'model' => 'phi',
    //         'prompt' => 'hello',
    //         'stream' => false
    //     ])->json();
    // }

    public function stream(string $prompt, string $model)
    {
        return Http::withOptions([
            'stream' => true,
        ])->post(
            config('ai.providers.ollama.url'), // ✅ FIXED
            [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => true
            ]
        );
    }
}