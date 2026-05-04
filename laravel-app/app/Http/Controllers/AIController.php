<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AI\AIManager;
use App\Services\AI\MemoryService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AIController extends Controller
{
    public function chat(Request $request, AIManager $ai)
    {
        $request->validate([
            'prompt'     => 'required|string|max:4000',
            'session_id' => 'required|string|max:100',
            'model'      => 'nullable|string|max:50',
            'system'     => 'nullable|string|max:1000',
        ]);

        $response = $ai->generateWithMemory(
            $request->prompt,
            $request->session_id,
            $request->model,
            $request->system
        );

        return response()->json($response->toArray(), $response->success ? 200 : 503);
    }

    public function stream(Request $request, AIManager $ai)
    {
        $request->validate([
            'prompt'     => 'required|string|max:4000',
            'session_id' => 'required|string|max:100',
            'model'      => 'nullable|string|max:50',
            'system'     => 'nullable|string|max:1000',
        ]);

        try {
            $result = $ai->streamWithMemory(
                $request->prompt,
                $request->session_id,
                $request->model,
                $request->system
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        $memory = app(MemoryService::class);

        return new StreamedResponse(function () use ($result, $memory) {
            $body = $result['stream']->getBody();
            $conversation = $result['conversation'];
            $fullResponse = '';

            while (!$body->eof()) {
                $chunk = $body->read(1024);

                foreach (explode("\n", $chunk) as $line) {
                    if (empty($line)) continue;

                    $json = json_decode($line, true);
                    if (!$json) continue;

                    $text = $json['message']['content'] ?? '';

                    if ($text !== '') {
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
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
        ]);
    }

    public function sse(Request $request, AIManager $ai)
    {
        $request->validate([
            'prompt'     => 'required|string|max:4000',
            'session_id' => 'required|string|max:100',
            'model'      => 'nullable|string|max:50',
            'system'     => 'nullable|string|max:1000',
        ]);

        try {
            $result = $ai->streamWithMemory(
                $request->prompt,
                $request->session_id,
                $request->model,
                $request->system
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        $memory = app(MemoryService::class);

        return new StreamedResponse(function () use ($result, $memory) {
            $stream = $result['stream'];
            $conversation = $result['conversation'];
            $fullResponse = '';

            foreach ($stream->getBody() as $chunk) {
                foreach (explode("\n", $chunk) as $line) {
                    if (empty($line)) continue;

                    $data = json_decode($line, true);
                    if (!$data) continue;

                    $text = $data['message']['content'] ?? '';

                    if ($text !== '') {
                        $fullResponse .= $text;
                        echo "event: message\n";
                        echo "data: " . str_replace("\n", "\\n", $text) . "\n\n";
                        ob_flush();
                        flush();
                    }

                    if (!empty($data['done'])) {
                        echo "event: done\n";
                        echo "data: true\n\n";
                        ob_flush();
                        flush();
                    }
                }
            }

            $memory->addMessage($conversation, 'assistant', $fullResponse);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
