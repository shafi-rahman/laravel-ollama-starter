<?php
namespace App\Services\AI;

use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\MemoryService;

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

    public function generateWithMemory(string $prompt, string $sessionId, ?string $modelKey = null)
    {
        $memory = app(MemoryService::class);

        $conversation = $memory->getOrCreateConversation($sessionId);

        $memory->addMessage($conversation, 'user', $prompt);

        $history = $memory->getHistory($conversation);

        $compiledPrompt = $this->compileHistory($history);

        [$providerName, $model] = $this->resolveModel($modelKey ?? 'phi');
        $provider = $this->resolveProvider($providerName);

        $raw = $provider->generate($compiledPrompt, $model);

        $responseText = trim($raw['response'] ?? '');

        $memory->addMessage($conversation, 'assistant', $responseText);

        return new AIResponse(
            success: true,
            model: $modelKey ?? 'phi',
            message: $responseText
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

    public function streamWithMemory(string $prompt, string $sessionId, ?string $modelKey = null) {
        $memory = app(MemoryService::class);

        $conversation = $memory->getOrCreateConversation($sessionId);

        $memory->addMessage($conversation, 'user', $prompt);

        $history = $memory->getHistory($conversation);

        $compiledPrompt = $this->compileHistory($history);

        [$providerName, $model] = $this->resolveModel($modelKey ?? 'phi');
        $provider = $this->resolveProvider($providerName);

        return [
            'stream' => $provider->stream($compiledPrompt, $model),
            'conversation' => $conversation,
        ];
    }

    private function compileHistory(array $history): string
    {
        $text = '';

        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $text .= "{$role}: {$msg['content']}\n";
        }

        return $text;
    }
}