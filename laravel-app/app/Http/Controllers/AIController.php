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
            'model' => 'nullable|string'
        ]);

        $response = $ai->generate(
            $request->prompt,
            $request->model
        );

        return response()->json($response->toArray());
    }

    
    public function stream(Request $request, AIManager $ai)
    {
        $request->validate([
            'prompt' => 'required|string',
            'model' => 'nullable|string'
        ]);

        return new StreamedResponse(function () use ($request, $ai) {

            $response = $ai->stream(
                $request->prompt,
                $request->model
            );

            $body = $response->getBody();

            while (!$body->eof()) {
                $chunk = $body->read(1024);

                if ($chunk) {
                    echo $chunk;
                    ob_flush();
                    flush();
                }
            }

        }, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}