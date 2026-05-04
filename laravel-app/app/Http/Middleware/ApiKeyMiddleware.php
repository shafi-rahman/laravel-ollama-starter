<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-Key');
        $configured = config('ai.api_key');

        if (!$key || $key !== $configured) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: invalid or missing X-API-Key header',
            ], 401);
        }

        return $next($request);
    }
}
