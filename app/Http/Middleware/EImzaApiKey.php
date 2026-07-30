<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EImzaApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-EImza-Api-Key');

        if (!$apiKey || !hash_equals(config('e-imza.api_key'), $apiKey)) {
            return response()->json(['message' => 'Geçersiz API anahtarı.'], 401);
        }

        return $next($request);
    }
}
