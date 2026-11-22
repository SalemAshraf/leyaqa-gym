<?php

namespace App\Http\Middleware;

use App\Models\MemberPortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Member portal pages: resolve the member from the bounded portal session
 * stored in the web session after the single-use link exchange.
 */
class PortalSessionAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('member_portal_session_id');
        $session = $id ? MemberPortalSession::withoutGlobalScopes()->with('member.gym')->find($id) : null;

        if (! $session || ! $session->isValid() || ! $session->member) {
            $request->session()->forget('member_portal_session_id');

            return redirect()->route('portal.expired');
        }

        $session->touchLastUsed();
        $request->attributes->set('member', $session->member);

        return $next($request);
    }
}
