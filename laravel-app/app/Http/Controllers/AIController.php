<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AILog;
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

        $start    = microtime(true);
        $response = $ai->generateWithMemory(
            $request->prompt,
            $request->session_id,
            $request->model,
            $request->system
        );
        $duration = (int) round((microtime(true) - $start) * 1000);

        AILog::create([
            'session_id'     => $request->session_id,
            'model'          => $request->model ?? 'phi',
            'endpoint'       => 'chat',
            'prompt_preview' => mb_substr($request->prompt, 0, 200),
            'duration_ms'    => $duration,
            'status'         => $response->success ? 'success' : 'error',
            'error'          => $response->success ? null : $response->message,
        ]);

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

        $start = microtime(true);

        try {
            $result = $ai->streamWithMemory(
                $request->prompt,
                $request->session_id,
                $request->model,
                $request->system
            );
        } catch (\Exception $e) {
            AILog::create([
                'session_id'     => $request->session_id,
                'model'          => $request->model ?? 'phi',
                'endpoint'       => 'stream',
                'prompt_preview' => mb_substr($request->prompt, 0, 200),
                'status'         => 'error',
                'error'          => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        $memory    = app(MemoryService::class);
        $sessionId = $request->session_id;
        $model     = $request->model ?? 'phi';
        $preview   = mb_substr($request->prompt, 0, 200);

        return new StreamedResponse(function () use ($result, $memory, $sessionId, $model, $preview, $start) {
            $body         = $result['stream']->getBody();
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

            AILog::create([
                'session_id'     => $sessionId,
                'model'          => $model,
                'endpoint'       => 'stream',
                'prompt_preview' => $preview,
                'duration_ms'    => (int) round((microtime(true) - $start) * 1000),
                'status'         => 'success',
            ]);
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

        $start = microtime(true);

        try {
            $result = $ai->streamWithMemory(
                $request->prompt,
                $request->session_id,
                $request->model,
                $request->system
            );
        } catch (\Exception $e) {
            AILog::create([
                'session_id'     => $request->session_id,
                'model'          => $request->model ?? 'phi',
                'endpoint'       => 'sse',
                'prompt_preview' => mb_substr($request->prompt, 0, 200),
                'status'         => 'error',
                'error'          => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        $memory    = app(MemoryService::class);
        $sessionId = $request->session_id;
        $model     = $request->model ?? 'phi';
        $preview   = mb_substr($request->prompt, 0, 200);

        return new StreamedResponse(function () use ($result, $memory, $sessionId, $model, $preview, $start) {
            $body         = $result['stream']->getBody();
            $conversation = $result['conversation'];
            $fullResponse = '';

            while (!$body->eof()) {
                $chunk = $body->read(1024);

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

            AILog::create([
                'session_id'     => $sessionId,
                'model'          => $model,
                'endpoint'       => 'sse',
                'prompt_preview' => $preview,
                'duration_ms'    => (int) round((microtime(true) - $start) * 1000),
                'status'         => 'success',
            ]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
