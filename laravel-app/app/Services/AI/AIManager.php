<?php
namespace App\Services\AI;

use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\MemoryService;

class AIManager
{
    public function generateWithMemory(string $prompt, string $sessionId, ?string $modelKey = null, ?string $systemPrompt = null): AIResponse
    {
        try {
            $memory = app(MemoryService::class);

            $conversation = $memory->getOrCreateConversation($sessionId);
            $memory->addMessage($conversation, 'user', $prompt);
            $history = $memory->getHistory($conversation);
            $messages = $this->buildMessages($history, $systemPrompt);

            [$providerName, $model] = $this->resolveModel($modelKey ?? 'phi');
            $provider = $this->resolveProvider($providerName);

            $raw = $provider->generate($messages, $model);
            $responseText = trim($raw['message']['content'] ?? '');

            $memory->addMessage($conversation, 'assistant', $responseText);

            return new AIResponse(success: true, model: $modelKey ?? 'phi', message: $responseText);
        } catch (\Exception $e) {
            return new AIResponse(success: false, model: $modelKey ?? 'phi', message: $e->getMessage());
        }
    }

    public function streamWithMemory(string $prompt, string $sessionId, ?string $modelKey = null, ?string $systemPrompt = null): array
    {
        $memory = app(MemoryService::class);

        $conversation = $memory->getOrCreateConversation($sessionId);
        $memory->addMessage($conversation, 'user', $prompt);
        $history = $memory->getHistory($conversation);
        $messages = $this->buildMessages($history, $systemPrompt);

        [$providerName, $model] = $this->resolveModel($modelKey ?? 'phi');
        $provider = $this->resolveProvider($providerName);

        return [
            'stream'       => $provider->stream($messages, $model),
            'conversation' => $conversation,
        ];
    }

    private function resolveModel(string $modelKey): array
    {
        foreach (config('ai.providers') as $providerName => $provider) {
            if (isset($provider['models'][$modelKey])) {
                return [$providerName, $provider['models'][$modelKey]];
            }
        }

        throw new \Exception("Model [$modelKey] not found in config");
    }

    private function resolveProvider(string $name)
    {
        return match ($name) {
            'ollama' => app(\App\Services\AI\Providers\OllamaProvider::class),
            default  => throw new \Exception("Provider [$name] not supported"),
        };
    }

    private function buildMessages(array $history, ?string $systemPrompt = null): array
    {
        $messages = [];

        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach (array_slice($history, -10) as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        return $messages;
    }
}
