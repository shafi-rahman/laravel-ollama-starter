<?php
namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\Message;

class MemoryService
{
    public function getOrCreateConversation(string $sessionId): Conversation
    {
        return Conversation::firstOrCreate([
            'session_id' => $sessionId
        ]);
    }

    public function addMessage(Conversation $conversation, string $role, string $content): void
    {
        $conversation->messages()->create([
            'role' => $role,
            'content' => $content
        ]);
    }

    public function getHistory(Conversation $conversation, int $limit = 10): array
    {
        return $conversation->messages()
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content
            ])
            ->toArray();
    }
}