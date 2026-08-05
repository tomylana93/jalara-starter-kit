<?php

use App\Enums\UserStatus;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns the authenticated user as JSON API', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.email', $user->email);

    expect($response->json('data.attributes'))->not->toHaveKeys([
        'phone',
        'status',
        'must_change_password',
        'last_login_at',
        'failed_login_attempts',
        'suspended_until',
    ]);
});

it('accepts a personal access token from a client that has no session', function () {
    $user = User::factory()->create();
    $token = $user->createToken('cli')->plainTextToken;

    getJson(route('api.v1.me'), ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('rejects a token whose account was disabled after it was issued', function () {
    $user = User::factory()->create();
    $token = $user->createToken('cli')->plainTextToken;

    $user->forceFill(['status' => UserStatus::Disabled])->save();

    getJson(route('api.v1.me'), ['Authorization' => "Bearer {$token}"])
        ->assertForbidden();
});

it('rejects a revoked personal access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('cli')->plainTextToken;

    $user->tokens()->delete();

    getJson(route('api.v1.me'), ['Authorization' => "Bearer {$token}"])
        ->assertUnauthorized();
});

it('rejects guests', function () {
    getJson(route('api.v1.me'))
        ->assertUnauthorized();
});

it('rejects unverified users', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->getJson(route('api.v1.me'))
        ->assertForbidden();
});
