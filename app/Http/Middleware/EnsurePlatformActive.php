<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $gym = $request->user()?->gym;

        if ($gym && ! $gym->platformAccessible()) {
            return redirect()->route('billing.show');
        }

        return $next($request);
    }
}
