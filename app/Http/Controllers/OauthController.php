<?php

namespace App\Http\Controllers;

use App\Models\OauthClient;
use App\Models\OauthCode;
use App\Models\OauthToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * OAuth 2.1 authorization server for the read-only AI connector (what Claude's "Add custom connector" needs):
 * discovery metadata, dynamic client registration for public clients, authorization code + PKCE (S256 only), refresh with rotation.
 * The consent screen is shown to a signed-in gym owner, and the token is bound to that user, gym and host.
 * Every token only reaches the read-only /mcp tools.
 */
class OauthController extends Controller
{
    public const SCOPE = 'gym:read';

    private const ACCESS_TTL = 3600;

    private const REFRESH_DAYS = 30;

    public static function base(Request $r): string
    {
        return $r->getSchemeAndHttpHost();
    }

    /** May this user connect an AI app, and may it keep reading? Gym owner, accessible gym, AI switch on. */
    public static function eligible(?User $u): bool
    {
        $gym = $u?->gym;

        return (bool) ($u && $u->isOwner() && $gym && $gym->platformAccessible() && $gym->ai_enabled);
    }

    public function resourceMetadata(Request $r)
    {
        $b = self::base($r);

        return response()->json(['resource' => $b.'/mcp', 'authorization_servers' => [$b], 'scopes_supported' => [self::SCOPE], 'bearer_methods_supported' => ['header'], 'resource_name' => 'جامد']);
    }

    public function serverMetadata(Request $r)
    {
        $b = self::base($r);

        return response()->json([
            'issuer' => $b, 'authorization_endpoint' => $b.'/oauth/authorize', 'token_endpoint' => $b.'/oauth/token',
            'registration_endpoint' => $b.'/oauth/register', 'revocation_endpoint' => $b.'/oauth/revoke',
            'response_types_supported' => ['code'], 'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'], 'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => [self::SCOPE, 'offline_access'],
        ]);
    }

    /** Dynamic client registration (RFC 7591), public clients only. Redirect URIs are limited to Claude's hosted callback and loopback (Claude Code). */
    public function register(Request $r)
    {
        $d = $r->json()->all();
        $uris = $d['redirect_uris'] ?? null;
        if (! is_array($uris) || ! $uris || count($uris) > 5 || collect($uris)->contains(fn ($u) => ! is_string($u) || ! self::redirectAllowed($u))) {
            return response()->json(['error' => 'invalid_redirect_uri', 'error_description' => 'Only Claude callback addresses are accepted.'], 400);
        }
        $method = $d['token_endpoint_auth_method'] ?? 'none';
        if ($method !== 'none') {
            return response()->json(['error' => 'invalid_client_metadata', 'error_description' => 'Only public clients are supported.'], 400);
        }
        // Registrations are cheap and unauthenticated: forget old ones that never got a token.
        OauthClient::where('created_at', '<', now()->subDays(7))->whereNotIn('client_id', OauthToken::select('client_id'))->delete();
        $name = Str::limit(trim(strip_tags((string) ($d['client_name'] ?? 'Claude'))), 100, '') ?: 'Claude';
        $c = OauthClient::create(['client_id' => 'oc_'.Str::random(32), 'name' => $name, 'redirect_uris' => array_values($uris)]);

        return response()->json([
            'client_id' => $c->client_id, 'client_id_issued_at' => $c->created_at->timestamp, 'client_name' => $name, 'redirect_uris' => $c->redirect_uris,
            'grant_types' => ['authorization_code', 'refresh_token'], 'response_types' => ['code'], 'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    private static function redirectAllowed(string $u): bool
    {
        return $u === 'https://claude.ai/api/mcp/auth_callback' || (bool) preg_match('#^http://(localhost|127\.0\.0\.1)(:\d{1,5})?/callback$#', $u);
    }

    /** Exact match, except that loopback redirects ignore the port (RFC 8252). */
    private static function redirectMatches(OauthClient $c, string $uri): bool
    {
        foreach ($c->redirect_uris as $reg) {
            if ($reg === $uri) {
                return true;
            }
            $strip = fn ($x) => preg_replace('#^(http://(?:localhost|127\.0\.0\.1)):\d+#', '$1', $x);
            if (str_starts_with($reg, 'http://') && $strip($reg) === $strip($uri)) {
                return true;
            }
        }

        return false;
    }

    private function fail(string $msg)
    {
        return response($msg, 400);
    }

    private function back(string $uri, array $q)
    {
        return $uri.(str_contains($uri, '?') ? '&' : '?').http_build_query(array_filter($q, fn ($v) => $v !== null));
    }

    public function authorizeForm(Request $r)
    {
        $c = OauthClient::where('client_id', (string) $r->query('client_id'))->first();
        $uri = (string) $r->query('redirect_uri');
        if (! $c || ! self::redirectMatches($c, $uri)) {
            return $this->fail('Unknown application or redirect address.'); // never redirect to an unverified address
        }
        $state = $r->query('state') !== null ? Str::limit((string) $r->query('state'), 500, '') : null;
        $err = fn ($e) => Inertia::location($this->back($uri, ['error' => $e, 'state' => $state]));
        if ($r->query('response_type') !== 'code') {
            return $err('unsupported_response_type');
        }
        $challenge = (string) $r->query('code_challenge');
        if ($r->query('code_challenge_method') !== 'S256' || ! preg_match('/^[A-Za-z0-9_-]{43}$/', $challenge)) {
            return $err('invalid_request');
        }
        $resource = $r->query('resource');
        if ($resource !== null && rtrim((string) $resource, '/') !== self::base($r).'/mcp') {
            return $err('invalid_target');
        }
        $user = $r->user();
        $gym = $user->gym;
        $blocked = ! $user->isOwner() ? 'not_owner' : (! $gym->platformAccessible() ? 'gym_inactive' : (! $gym->ai_enabled ? 'ai_off' : null));
        $rid = Str::random(32);
        $r->session()->put('oauth_req', ['rid' => $rid, 'client_id' => $c->client_id, 'redirect_uri' => $uri, 'challenge' => $challenge, 'state' => $state, 'host' => strtolower($r->getHttpHost())]);

        return Inertia::render('Oauth/Consent', [
            'rid' => $rid, 'blocked' => $blocked, 'app' => $c->name, 'redirectHost' => parse_url($uri, PHP_URL_HOST),
            'user' => ['name' => $user->name, 'email' => $user->email], 'gym' => $gym->name,
        ]);
    }

    public function decide(Request $r)
    {
        $d = $r->validate(['rid' => 'required|string', 'decision' => 'required|in:allow,deny']);
        $req = $r->session()->pull('oauth_req');
        if (! $req || ! hash_equals($req['rid'], $d['rid']) || $req['host'] !== strtolower($r->getHttpHost())) {
            abort(400, 'This request expired. Start again from the AI app.');
        }
        $c = OauthClient::where('client_id', $req['client_id'])->first();
        abort_unless($c && self::redirectMatches($c, $req['redirect_uri']), 400);
        $user = $r->user();
        if ($d['decision'] === 'deny' || ! self::eligible($user)) {
            return Inertia::location($this->back($req['redirect_uri'], ['error' => 'access_denied', 'state' => $req['state']]));
        }
        $raw = Str::random(48);
        OauthCode::create([
            'code_hash' => hash('sha256', $raw), 'client_id' => $c->client_id, 'user_id' => $user->id, 'gym_id' => $user->gym_id,
            'redirect_uri' => $req['redirect_uri'], 'code_challenge' => $req['challenge'], 'host' => $req['host'], 'expires_at' => now()->addMinutes(10),
        ]);

        return Inertia::location($this->back($req['redirect_uri'], ['code' => $raw, 'state' => $req['state']]));
    }

    private function oerr(string $e, string $m, int $status = 400)
    {
        return response()->json(['error' => $e, 'error_description' => $m], $status, ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }

    public function token(Request $r)
    {
        $c = OauthClient::where('client_id', (string) $r->input('client_id'))->first();
        if (! $c) {
            return $this->oerr('invalid_client', 'Unknown client.', 401);
        }

        return match ((string) $r->input('grant_type')) {
            'authorization_code' => $this->exchangeCode($r, $c),
            'refresh_token' => $this->refresh($r, $c),
            default => $this->oerr('unsupported_grant_type', 'Use authorization_code or refresh_token.'),
        };
    }

    private function exchangeCode(Request $r, OauthClient $c)
    {
        $code = OauthCode::where('code_hash', hash('sha256', (string) $r->input('code')))->first();
        if (! $code || $code->client_id !== $c->client_id) {
            return $this->oerr('invalid_grant', 'Invalid code.');
        }
        if ($code->used_at) {
            // A code used twice: treat every token that came from this connection as stolen.
            OauthToken::where('family', 'c'.$code->id)->update(['revoked_at' => now()]);

            return $this->oerr('invalid_grant', 'Code already used.');
        }
        $verifier = (string) $r->input('code_verifier');
        $ok = $code->expires_at->isFuture() && hash_equals($code->redirect_uri, (string) $r->input('redirect_uri'))
            && preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)
            && hash_equals($code->code_challenge, rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='));
        if (! $ok) {
            return $this->oerr('invalid_grant', 'Code expired or verification failed.');
        }
        // Single use even under concurrent requests.
        if (OauthCode::where('id', $code->id)->whereNull('used_at')->update(['used_at' => now()]) !== 1) {
            return $this->oerr('invalid_grant', 'Code already used.');
        }
        $user = User::find($code->user_id);
        if (! self::eligible($user)) {
            return $this->oerr('invalid_grant', 'This account can no longer connect.');
        }

        return $this->issue('c'.$code->id, $c->client_id, $user, $code->host);
    }

    private function refresh(Request $r, OauthClient $c)
    {
        $t = OauthToken::where('refresh_hash', hash('sha256', (string) $r->input('refresh_token')))->first();
        if (! $t || $t->client_id !== $c->client_id || $t->revoked_at || $t->refresh_expires_at->isPast()) {
            return $this->oerr('invalid_grant', 'Invalid refresh token.');
        }
        if ($t->rotated_at) {
            // An already-used refresh token came back: end the whole connection.
            OauthToken::where('family', $t->family)->update(['revoked_at' => now()]);

            return $this->oerr('invalid_grant', 'Refresh token already used.');
        }
        if (OauthToken::where('id', $t->id)->whereNull('rotated_at')->update(['rotated_at' => now()]) !== 1) {
            return $this->oerr('invalid_grant', 'Refresh token already used.');
        }
        $user = User::find($t->user_id);
        if (! self::eligible($user)) {
            OauthToken::where('family', $t->family)->update(['revoked_at' => now()]);

            return $this->oerr('invalid_grant', 'This account can no longer connect.');
        }

        return $this->issue($t->family, $c->client_id, $user, $t->host);
    }

    private function issue(string $family, string $clientId, User $user, string $host)
    {
        $access = 'gmo_'.Str::random(48);
        $refresh = 'gmr_'.Str::random(56);
        OauthToken::create([
            'family' => $family, 'access_hash' => hash('sha256', $access), 'refresh_hash' => hash('sha256', $refresh), 'client_id' => $clientId,
            'user_id' => $user->id, 'gym_id' => $user->gym_id, 'host' => $host,
            'access_expires_at' => now()->addSeconds(self::ACCESS_TTL), 'refresh_expires_at' => now()->addDays(self::REFRESH_DAYS),
        ]);

        return response()->json(['access_token' => $access, 'token_type' => 'Bearer', 'expires_in' => self::ACCESS_TTL, 'refresh_token' => $refresh, 'scope' => self::SCOPE], 200, ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }

    /** RFC 7009. Always answers 200 so it cannot be used to probe for tokens. Ends the whole connection. */
    public function revoke(Request $r)
    {
        $h = hash('sha256', (string) $r->input('token'));
        $t = OauthToken::where('access_hash', $h)->orWhere('refresh_hash', $h)->first();
        if ($t) {
            OauthToken::where('family', $t->family)->update(['revoked_at' => now()]);
        }

        return response()->json((object) [], 200, ['Cache-Control' => 'no-store']);
    }
}
