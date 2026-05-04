<?php
namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIProvider;

class OllamaProvider implements AIProvider
{

    public function generate(string $prompt, string $model): array
    {
        set_time_limit(600);
        try {
            $response = Http::timeout(600)->post(
                config('ai.providers.ollama.url'),
                ['model' => $model, 'prompt' => $prompt, 'stream' => false]
            );

            if ($response->failed()) {
                throw new \RuntimeException("Ollama returned HTTP {$response->status()}");
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Ollama is unreachable. Is it running? ' . $e->getMessage());
        }
    }

    public function stream(string $prompt, string $model): mixed
    {
        try {
            $response = Http::withOptions(['stream' => true])->post(
                config('ai.providers.ollama.url'),
                ['model' => $model, 'prompt' => $prompt, 'stream' => true]
            );

            if ($response->failed()) {
                throw new \RuntimeException("Ollama returned HTTP {$response->status()}");
            }

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Ollama is unreachable. Is it running? ' . $e->getMessage());
        }
    }
}