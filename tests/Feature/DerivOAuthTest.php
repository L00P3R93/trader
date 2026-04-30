<?php

use App\Models\DerivConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('unauthenticated users are redirected from connect route', function () {
    $this->get(route('deriv.connect'))->assertRedirect(route('login'));
});

test('redirect stores pkce state in session and redirects to deriv', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('deriv.connect'));

    $response->assertRedirectContains('auth.deriv.com/oauth2/auth');
    $response->assertRedirectContains('code_challenge');
    $response->assertRedirectContains('response_type=code');

    expect(session('deriv_oauth_state'))->not->toBeNull()
        ->and(session('deriv_code_verifier'))->not->toBeNull();
});

test('callback stores connection after successful token exchange', function () {
    $user = User::factory()->create();

    Http::fake([
        'auth.deriv.com/oauth2/token' => Http::response([
            'access_token' => 'ory_at_fake_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'trade account_manage',
        ], 200),
    ]);

    $state = 'test-state-value';

    $this->actingAs($user)
        ->withSession([
            'deriv_oauth_state' => $state,
            'deriv_code_verifier' => 'test-verifier',
        ])
        ->get(route('deriv.callback', ['code' => 'auth-code-123', 'state' => $state]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($user->derivConnection)->not->toBeNull()
        ->and($user->hasDerivConnected())->toBeTrue();
});

test('callback rejects mismatched state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            'deriv_oauth_state' => 'correct-state',
            'deriv_code_verifier' => 'verifier',
        ])
        ->get(route('deriv.callback', ['code' => 'auth-code', 'state' => 'wrong-state']))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');

    expect($user->hasDerivConnected())->toBeFalse();
});

test('callback handles deriv authorization denied', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('deriv.callback', [
            'error' => 'access_denied',
            'error_description' => 'User cancelled',
        ]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');
});

test('callback handles token exchange failure', function () {
    $user = User::factory()->create();

    Http::fake([
        'auth.deriv.com/oauth2/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $state = 'test-state';

    $this->actingAs($user)
        ->withSession([
            'deriv_oauth_state' => $state,
            'deriv_code_verifier' => 'verifier',
        ])
        ->get(route('deriv.callback', ['code' => 'bad-code', 'state' => $state]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');
});

test('disconnect removes deriv connection', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('deriv.disconnect'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($user->hasDerivConnected())->toBeFalse();
});

test('callback updates existing connection on reconnect', function () {
    $user = User::factory()->create();
    DerivConnection::factory()->create(['user_id' => $user->id, 'access_token' => 'old-token']);

    Http::fake([
        'auth.deriv.com/oauth2/token' => Http::response([
            'access_token' => 'ory_at_new_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
    ]);

    $state = 'test-state';

    $this->actingAs($user)
        ->withSession([
            'deriv_oauth_state' => $state,
            'deriv_code_verifier' => 'verifier',
        ])
        ->get(route('deriv.callback', ['code' => 'new-code', 'state' => $state]));

    expect($user->derivConnection()->count())->toBe(1);
});
