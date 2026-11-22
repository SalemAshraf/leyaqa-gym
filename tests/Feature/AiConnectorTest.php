<?php

use App\Models\Member;
use App\Models\OauthToken;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

const AI_CB = 'https://claude.ai/api/mcp/auth_callback';

function aiPkce(): array
{
    $v = rtrim(strtr(base64_encode(random_bytes(40)), '+/', '-_'), '=');

    return [$v, rtrim(strtr(base64_encode(hash('sha256', $v, true)), '+/', '-_'), '=')];
}

function aiRegisterClient($t, array $uris = [AI_CB]): string
{
    return $t->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => $uris, 'token_endpoint_auth_method' => 'none'])->assertCreated()->json('client_id');
}

/** Runs the consent step as $user and returns the query Claude would receive back. */
function aiConsent($t, $user, string $client, string $challenge, string $decision = 'allow', string $state = 'st1'): array
{
    $q = http_build_query(['response_type' => 'code', 'client_id' => $client, 'redirect_uri' => AI_CB, 'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'state' => $state]);
    $page = $t->actingAs($user->fresh(), 'web')->get('/oauth/authorize?'.$q)->assertOk();
    $rid = $page->viewData('page')['props']['rid'];
    $res = $t->actingAs($user->fresh(), 'web')->post('/oauth/authorize', ['rid' => $rid, 'decision' => $decision]);
    parse_str((string) parse_url($res->headers->get('Location'), PHP_URL_QUERY), $out);

    return $out;
}

function aiExchange($t, string $client, string $code, string $verifier, string $uri = AI_CB)
{
    Auth::guard('web')->forgetUser();

    return $t->post('/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => $client, 'code' => $code, 'redirect_uri' => $uri, 'code_verifier' => $verifier]);
}

function aiConnect($t, $user): array
{
    $user->gym->forceFill(['ai_enabled' => true])->save();
    [$v, $c] = aiPkce();
    $client = aiRegisterClient($t);
    $out = aiConsent($t, $user, $client, $c);
    $tok = aiExchange($t, $client, $out['code'], $v)->assertOk()->json();

    return [$client, $tok];
}

function rpc($t, ?string $token, string $method, array $params = [], $id = 1)
{
    Auth::guard('web')->forgetUser();

    return $t->withHeaders($token ? ['Authorization' => 'Bearer '.$token] : [])->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params]);
}

it('serves discovery documents for this host with PKCE S256 and registration', function () {
    $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk()
        ->assertJsonPath('resource', url('/mcp'))->assertJsonPath('scopes_supported.0', 'gym:read');
    $m = $this->getJson('/.well-known/oauth-authorization-server')->assertOk()->json();
    expect($m['code_challenge_methods_supported'])->toBe(['S256'])
        ->and($m['registration_endpoint'])->toBe(url('/oauth/register'))
        ->and($m['token_endpoint_auth_methods_supported'])->toBe(['none']);
});

it('answers 401 with the resource metadata pointer when the token is missing or wrong', function () {
    $res = rpc($this, null, 'tools/list')->assertStatus(401);
    expect($res->headers->get('WWW-Authenticate'))->toContain('resource_metadata="'.url('/.well-known/oauth-protected-resource').'"');
    rpc($this, 'gmo_nope', 'tools/list')->assertStatus(401);
    rpc($this, 'random-string', 'tools/list')->assertStatus(401);
});

it('registers only public clients with Claude callback addresses', function () {
    $this->postJson('/oauth/register', ['redirect_uris' => ['https://evil.example/cb']])->assertStatus(400)->assertJsonPath('error', 'invalid_redirect_uri');
    $this->postJson('/oauth/register', ['redirect_uris' => [AI_CB], 'token_endpoint_auth_method' => 'client_secret_basic'])->assertStatus(400);
    $this->postJson('/oauth/register', ['redirect_uris' => []])->assertStatus(400);
    $this->postJson('/oauth/register', ['redirect_uris' => ['http://127.0.0.1:3118/callback']])->assertCreated();
});

it('guards the consent page: login, verified client and redirect, S256, owner role, switch and active gym', function () {
    [$gym, $owner] = makeOwner();
    $client = aiRegisterClient($this);
    [$v, $c] = aiPkce();
    $base = ['response_type' => 'code', 'client_id' => $client, 'redirect_uri' => AI_CB, 'code_challenge' => $c, 'code_challenge_method' => 'S256', 'state' => 's'];

    $this->get('/oauth/authorize?'.http_build_query($base))->assertRedirect(route('login'));
    $this->actingAs($owner->fresh(), 'web')->get('/oauth/authorize?'.http_build_query(['redirect_uri' => 'https://evil.example/cb'] + $base))->assertStatus(400);
    $this->actingAs($owner->fresh(), 'web')->get('/oauth/authorize?'.http_build_query(['client_id' => 'oc_nope'] + $base))->assertStatus(400);
    $bad = $this->actingAs($owner->fresh(), 'web')->get('/oauth/authorize?'.http_build_query(['code_challenge_method' => 'plain'] + $base));
    expect($bad->headers->get('Location'))->toStartWith(AI_CB)->toContain('error=invalid_request');

    // AI switch off by default.
    $this->actingAs($owner->fresh(), 'web')->get('/oauth/authorize?'.http_build_query($base))
        ->assertOk()->assertInertia(fn ($p) => $p->component('Oauth/Consent')->where('blocked', 'ai_off')->where('redirectHost', 'claude.ai'));

    // Staff cannot approve.
    $staff = makeStaff($gym);
    $gym->forceFill(['ai_enabled' => true])->save();
    $this->actingAs($staff->fresh(), 'web')->get('/oauth/authorize?'.http_build_query($base))
        ->assertOk()->assertInertia(fn ($p) => $p->where('blocked', 'not_owner'));

    // A billing-locked gym cannot approve.
    $gym->forceFill(['platform_status' => 'suspended'])->save();
    $this->actingAs($owner->fresh(), 'web')->get('/oauth/authorize?'.http_build_query($base))
        ->assertOk()->assertInertia(fn ($p) => $p->where('blocked', 'gym_inactive'));
});

it('runs the full flow: consent, PKCE exchange, MCP read, and gym isolation', function () {
    [$gym, $owner] = makeOwner();
    $member = Member::factory()->create(['gym_id' => $gym->id, 'name' => 'عضو عندنا']);
    $plan = \App\Models\Plan::factory()->create(['gym_id' => $gym->id]);
    $sub = \App\Models\Subscription::factory()->create(['gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id]);
    Payment::factory()->create(['gym_id' => $gym->id, 'subscription_id' => $sub->id, 'amount' => 500, 'paid_at' => now()]);

    [$otherGym, $otherOwner] = makeOwner();
    Auth::guard('web')->setUser($otherOwner);
    $stranger = Member::factory()->create(['gym_id' => $otherGym->id, 'name' => 'عضو جيم تاني']);
    Auth::guard('web')->forgetUser();

    [$client, $tok] = aiConnect($this, $owner);
    expect($tok['access_token'])->toStartWith('gmo_')->and($tok['refresh_token'])->toStartWith('gmr_');

    $tools = collect(rpc($this, $tok['access_token'], 'tools/list')->assertOk()->json('result.tools'))->pluck('name')->all();
    expect($tools)->toContain('gym_stats', 'search_members', 'get_member', 'expiring_subscriptions', 'list_debtors', 'today_checkins', 'revenue_summary', 'today_classes');

    $stats = json_decode(rpc($this, $tok['access_token'], 'tools/call', ['name' => 'gym_stats'])->assertOk()->json('result.content.0.text'), true);
    expect($stats['active_members'])->toBe(1)->and($stats['gym'])->toBe($gym->name);

    $res = rpc($this, $tok['access_token'], 'tools/call', ['name' => 'search_members', 'arguments' => ['query' => 'عضو']])->assertOk()->json('result.content.0.text');
    expect($res)->toContain('عضو عندنا')->not->toContain('عضو جيم تاني');

    // Another gym's member is unreachable by id.
    rpc($this, $tok['access_token'], 'tools/call', ['name' => 'get_member', 'arguments' => ['id' => $stranger->id]])->assertJsonPath('error.code', -32602);

    // No write tool exists and nothing was written.
    $before = Member::withoutGlobalScopes()->count();
    rpc($this, $tok['access_token'], 'tools/call', ['name' => 'delete_member', 'arguments' => ['id' => $member->id]])->assertJsonPath('error.code', -32602);
    expect(Member::withoutGlobalScopes()->count())->toBe($before);

    // The authorization code cannot be used twice.
    Auth::guard('web')->forgetUser();
    aiExchange($this, $client, 'forged-code', 'x')->assertStatus(400);
});

it('rotates refresh tokens and kills the connection when a used one comes back', function () {
    [$gym, $owner] = makeOwner();
    [$client, $tok] = aiConnect($this, $owner);

    $r1 = $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $client, 'refresh_token' => $tok['refresh_token']])->assertOk()->json();
    // Old access token is rotated out.
    rpc($this, $tok['access_token'], 'tools/list')->assertStatus(401);
    rpc($this, $r1['access_token'], 'tools/list')->assertOk();
    // Reusing the old refresh token kills the whole family.
    $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $client, 'refresh_token' => $tok['refresh_token']])->assertStatus(400);
    rpc($this, $r1['access_token'], 'tools/list')->assertStatus(401);
});

it('stops serving tokens when the owner loses eligibility', function () {
    [$gym, $owner] = makeOwner();
    [$client, $tok] = aiConnect($this, $owner);
    rpc($this, $tok['access_token'], 'tools/list')->assertOk();

    $gym->forceFill(['ai_enabled' => false])->save();
    rpc($this, $tok['access_token'], 'tools/list')->assertStatus(401);

    $gym->forceFill(['ai_enabled' => true, 'platform_status' => 'suspended'])->save();
    rpc($this, $tok['access_token'], 'tools/list')->assertStatus(401);

    $gym->forceFill(['platform_status' => 'active'])->save();
    $owner->fresh()->removeRole('owner');
    rpc($this, $tok['access_token'], 'tools/list')->assertStatus(401);
});

it('revokes a connection from the token endpoint and from settings', function () {
    [$gym, $owner] = makeOwner();
    [$client, $tok] = aiConnect($this, $owner);

    // Token endpoint revoke always answers 200.
    $this->post('/oauth/revoke', ['token' => 'unknown'])->assertOk();
    $this->post('/oauth/revoke', ['token' => $tok['refresh_token']])->assertOk();
    rpc($this, $tok['access_token'], 'tools/list')->assertStatus(401);

    // Settings disconnect: owner only, ends the connection, and works even for a locked gym.
    [$client2, $tok2] = aiConnect($this, $owner);
    $family = OauthToken::firstWhere('access_hash', hash('sha256', $tok2['access_token']))->family;
    $staff = makeStaff($gym);
    $this->actingAs($staff->fresh(), 'web')->delete('/settings/ai-connections/'.$family)->assertForbidden();
    $this->actingAs($owner->fresh(), 'web')->delete('/settings/ai-connections/'.$family)->assertRedirect();
    rpc($this, $tok2['access_token'], 'tools/list')->assertStatus(401);
});

it('lets the owner switch the connector on from settings and see connections', function () {
    [$gym, $owner] = makeOwner();
    $staff = makeStaff($gym);

    expect((bool) $gym->fresh()->ai_enabled)->toBeFalse();
    $this->actingAs($staff->fresh(), 'web')->patch('/settings/ai', ['enabled' => true])->assertForbidden();
    $this->actingAs($owner->fresh(), 'web')->patch('/settings/ai', ['enabled' => true])->assertRedirect();
    expect((bool) $gym->fresh()->ai_enabled)->toBeTrue();

    [$client, $tok] = aiConnect($this, $owner);
    $this->actingAs($owner->fresh(), 'web')->get('/settings')
        ->assertInertia(fn ($p) => $p->where('ai.enabled', true)->has('ai.connections', 1)->where('ai.connections.0.app', 'Claude')->missing('ai.connections.0.access_hash'));
});

it('treats id-less payloads as notifications and rejects malformed ones', function () {
    [$gym, $owner] = makeOwner();
    [$client, $tok] = aiConnect($this, $owner);

    $this->withHeaders(['Authorization' => 'Bearer '.$tok['access_token']])
        ->postJson('/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])->assertStatus(202);
    rpc($this, $tok['access_token'], 'nope/method')->assertJsonPath('error.code', -32601);
    $this->withHeaders(['Authorization' => 'Bearer '.$tok['access_token']])
        ->postJson('/mcp', ['jsonrpc' => '1.0', 'id' => 1, 'method' => 'ping'])->assertJsonPath('error.code', -32600);
    rpc($this, $tok['access_token'], 'ping')->assertOk();
});
