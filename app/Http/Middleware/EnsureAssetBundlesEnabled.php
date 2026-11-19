<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAssetBundlesEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('features.asset_bundles'), 404);

        return $next($request);
    }
}
