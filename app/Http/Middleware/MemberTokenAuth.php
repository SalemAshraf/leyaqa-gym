<?php

namespace App\Http\Middleware;

use App\Models\Member;
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
        $token = $request->bearerToken();
        $member = $token ? Member::withoutGlobalScopes()->where('portal_token', $token)->first() : null;

        abort_unless($member, 401, 'Invalid member token');

        $request->attributes->set('member', $member);

        return $next($request);
    }
}
