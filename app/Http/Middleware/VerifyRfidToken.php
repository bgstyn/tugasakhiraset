<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRfidToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow logged in web session users
        if ($request->user() || auth('web')->check() || auth()->check()) {
            return $next($request);
        }

        $expectedToken = config('services.rfid.token');

        // Retrieve token from Authorization header, X-API-Token header, or api_token query/request field
        $token = $request->bearerToken() ?: $request->input('api_token') ?: $request->header('X-API-Token');

        if (! is_string($token) || $token === '' || ! hash_equals((string) $expectedToken, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized RFID API request.'
            ], 401);
        }

        return $next($request);
    }
}
