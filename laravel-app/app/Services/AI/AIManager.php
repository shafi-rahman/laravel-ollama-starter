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

    public function generate(string $prompt, ?string $modelKey = null)
    {
        $modelKey = $modelKey ?? 'phi';

        [$providerName, $model] = $this->resolveModel($modelKey);

        $provider = $this->resolveProvider($providerName);

        $raw = $provider->generate($prompt, $model);

        return new AIResponse(
            success: true,
            model: $modelKey,
            message: trim($raw['response'] ?? '')
        );
    }

    public function stream(string $prompt, ?string $modelKey = null)
    {
        $modelKey = $modelKey ?? 'phi';

        [$providerName, $model] = $this->resolveModel($modelKey);

        $provider = $this->resolveProvider($providerName);

        return $provider->stream($prompt, $model);
    }

    private function resolveModel(string $modelKey): array
    {
        $providers = config('ai.providers');

        foreach ($providers as $providerName => $provider) {
            if (isset($provider['models'][$modelKey])) {
                return [$providerName, $provider['models'][$modelKey]];
            }
        }

        throw new \Exception("Model [$modelKey] not found");
    }

    private function resolveProvider(string $name)
    {
        return match ($name) {
            'ollama' => app(\App\Services\AI\Providers\OllamaProvider::class),

            // future
            // 'openai' => app(OpenAIProvider::class),

            default => throw new \Exception("Provider [$name] not supported"),
        };
    }
}