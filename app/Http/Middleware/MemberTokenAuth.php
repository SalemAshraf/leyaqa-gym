<?php

namespace App\Http\Middleware;

use App\Models\MemberPortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Member API auth: the member's private portal token as a Bearer token.
 * Members are not users, so the resolved member rides on the request.
 */
class MemberTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = MemberPortalSession::findValidByToken($request->bearerToken());

        abort_unless($session?->member, 401, 'Invalid member token');

        $request->attributes->set('member', $session->member);

        return $next($request);
    }
}
