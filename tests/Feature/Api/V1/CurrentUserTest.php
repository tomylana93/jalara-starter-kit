<?php

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
