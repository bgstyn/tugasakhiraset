<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffSessionMiddleware
{
    /**
     * Handle an incoming request.
     * Populates the staff_it session from the authenticated user's data
     * to maintain backward compatibility with views and audit trail.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Populate staff_it session from authenticated user data
            // This maintains backward compatibility with layout sidebar and audit trail
            if (!session()->has('staff_it') || session('staff_it.name') !== $user->name) {
                session([
                    'staff_it' => [
                        'name' => $user->name,
                        'position' => $user->position ?? $user->role,
                        'location' => $user->location ?? '-',
                    ],
                ]);
            }
        }

        return $next($request);
    }
}
