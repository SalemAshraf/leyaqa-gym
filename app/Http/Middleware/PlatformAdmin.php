<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Unverified emails cannot grant platform access: a tenant user who types an
        // admin address into their profile stays unverified until they prove ownership.
        abort_unless(
            $user && $user->hasVerifiedEmail() && in_array($user->email, config('platform.admin_emails', []), true),
            403
        );

        return $next($request);
    }
}
