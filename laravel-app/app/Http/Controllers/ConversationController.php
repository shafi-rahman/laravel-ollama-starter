<?php

namespace App\Http\Controllers;

use App\Models\AILog;
use App\Models\Conversation;

class ConversationController extends Controller
{
    public function index()
    {
        $conversations = Conversation::withCount('messages')
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($c) => [
                'session_id'    => $c->session_id,
                'message_count' => $c->messages_count,
                'last_message'  => mb_substr($c->messages->first()?->content ?? '', 0, 120),
                'last_role'     => $c->messages->first()?->role,
                'last_active'   => $c->updated_at?->toISOString(),
            ]);

        return response()->json($conversations);
    }

    public function show(string $sessionId)
    {
        $conversation = Conversation::where('session_id', $sessionId)
            ->with(['messages' => fn ($q) => $q->oldest()])
            ->firstOrFail();

        return response()->json([
            'session_id' => $conversation->session_id,
            'messages'   => $conversation->messages->map(fn ($m) => [
                'role'       => $m->role,
                'content'    => $m->content,
                'created_at' => $m->created_at->toISOString(),
            ]),
        ]);
    }

    public function logs()
    {
        return response()->json(
            AILog::latest()->limit(100)->get()
        );
    }
}
