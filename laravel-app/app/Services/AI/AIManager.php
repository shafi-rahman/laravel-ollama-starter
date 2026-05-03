<?php
namespace App\Services\AI;

use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\DTOs\AIResponse;

class AIManager
{
    protected $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    protected function resolveProvider()
    {
        return match (config('ai.provider')) {
            'ollama' => new OllamaProvider(),
            default => throw new \Exception('Invalid provider'),
        };
    }

    public function generate(string $prompt, ?string $model = null): AIResponse
    {
        $model = $model ?? config('ai.ollama.default_model');

        $raw = $this->provider->generate($prompt, $model);

        return new AIResponse(
            success: true,
            model: $model,
            message: trim($raw['response'] ?? '')
        );
    }
}