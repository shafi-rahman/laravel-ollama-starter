<?php
namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIProvider;

class OllamaProvider implements AIProvider
{
    public function generate(array $messages, string $model): array
    {
        set_time_limit(600);
        try {
            $response = Http::timeout(600)->post(
                config('ai.providers.ollama.url'),
                [
                    'model'      => $model,
                    'messages'   => $messages,
                    'stream'     => false,
                    'keep_alive' => '10m',
                    'options'    => [
                        'num_ctx'     => (int) config('ai.num_ctx', 2048),
                        'num_predict' => (int) config('ai.num_predict', 512),
                    ],
                ]
            );

            if ($response->failed()) {
                throw new \RuntimeException("Ollama returned HTTP {$response->status()}");
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Ollama is unreachable. Is it running? ' . $e->getMessage());
        }
    }

    public function stream(array $messages, string $model): mixed
    {
        try {
            $response = Http::timeout(300)->withOptions(['stream' => true])->post(
                config('ai.providers.ollama.url'),
                [
                    'model'      => $model,
                    'messages'   => $messages,
                    'stream'     => true,
                    'keep_alive' => '10m',
                    'options'    => [
                        'num_ctx'     => (int) config('ai.num_ctx', 2048),
                        'num_predict' => (int) config('ai.num_predict', 512),
                    ],
                ]
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
