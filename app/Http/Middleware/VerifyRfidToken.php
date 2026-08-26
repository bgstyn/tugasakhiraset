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
        $expectedToken = env('RFID_API_TOKEN', 'secret-rfid-token');

        // Retrieve token from Authorization header, X-API-Token header, or api_token query/request field
        $token = $request->bearerToken() ?: $request->input('api_token') ?: $request->header('X-API-Token');

        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized RFID API request.'
            ], 401);
        }

        return $next($request);
    }
}
