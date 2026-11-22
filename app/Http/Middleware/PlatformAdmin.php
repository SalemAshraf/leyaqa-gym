<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->user()?->email;

        abort_unless($email && in_array($email, config('platform.admin_emails', []), true), 403);

        return $next($request);
    }
}
