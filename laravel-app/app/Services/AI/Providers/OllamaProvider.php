<?php
namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIProvider;

class OllamaProvider implements AIProvider
{
    public function generate(string $prompt, string $model): array
    {
        return Http::timeout(120)->post(
            config('ai.ollama.url'),
            [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false
            ]
        )->json();
    }

    public function stream(string $prompt, string $model)
    {
        return Http::withOptions([
            'stream' => true,
        ])->post(config('ai.ollama.url'), [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => true
        ]);
    }
}