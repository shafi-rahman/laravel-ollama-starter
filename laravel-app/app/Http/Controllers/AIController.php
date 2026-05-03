<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AI\AIManager;

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
}