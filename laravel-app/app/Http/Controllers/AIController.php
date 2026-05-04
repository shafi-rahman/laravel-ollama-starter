<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AI\AIManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AIController extends Controller
{
    public function chat(Request $request, AIManager $ai)
    {
        $request->validate([
            'prompt' => 'required|string',
            'session_id' => 'required|string',
            'model' => 'nullable|string'
        ]);

        $response = $ai->generateWithMemory(
            $request->prompt,
            $request->session_id,
            $request->model,
            $request->system
        );

        return response()->json($response->toArray());
    }

    public function stream(Request $request, AIManager $ai)
    {
        $request->validate([
            'prompt' => 'required|string',
            'session_id' => 'required|string',
            'model' => 'nullable|string',
            'system' => 'nullable|string',
        ]);

        return new StreamedResponse(function () use ($request, $ai) {

            $memory = app(\App\Services\AI\MemoryService::class);

            $result = $ai->streamWithMemory(
                $request->prompt,
                $request->session_id,
                $request->model,
                $request->system
            );

            $response = $result['stream'];
            $conversation = $result['conversation'];

            $body = $response->getBody();

            $fullResponse = ''; 

            while (!$body->eof()) {
                $chunk = $body->read(1024);

                $lines = explode("\n", $chunk);

                foreach ($lines as $line) {
                    if (empty($line)) continue;

                    $json = json_decode($line, true);

                    if (!$json) continue;

                    if (isset($json['response'])) {
                        $text = $json['response'];

                        echo "data: " . $text . "\n\n";

                        $fullResponse .= $text;
                    }

                    if (!empty($json['done'])) {
                        echo "data: [DONE]\n\n";
                    }
                }

                ob_flush();
                flush();
            }

            $memory->addMessage($conversation, 'assistant', trim($fullResponse));

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}