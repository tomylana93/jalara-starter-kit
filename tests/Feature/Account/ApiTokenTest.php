<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

it('lists the tokens the user owns', function () {
    $user = User::factory()->create();
    $user->createToken('laptop');

    $other = User::factory()->create();
    $other->createToken('somebody else');

    actingAs($user)
        ->get(route('account.api-tokens.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/ApiTokens')
            ->has('tokens', 1)
            ->where('tokens.0.name', 'laptop'),
        );
});

it('never shares the token hash with the client', function () {
    $user = User::factory()->create();
    $user->createToken('laptop');

    $response = actingAs($user)->get(route('account.api-tokens.index'));

    $tokens = inertiaRows($response->viewData('page')['props']['tokens']);

    expect($tokens[0])->toHaveKeys(['id', 'name', 'last_used_at', 'created_at'])
        ->and($tokens[0])->not->toHaveKey('token');
});

it('issues a token and flashes the plain text exactly once', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.api-tokens.store'), ['name' => 'deploy bot'])
        ->assertRedirect();

    expect($user->tokens()->count())->toBe(1);

    /*
     * The plain text lives in the flash bag, so the follow-up render carries it
     * and the one after that does not.
     */
    $first = actingAs($user)->get(route('account.api-tokens.index'));
    $second = actingAs($user)->get(route('account.api-tokens.index'));

    expect($first->viewData('page')['flash'] ?? [])->toHaveKey('createdApiToken')
        ->and($second->viewData('page')['flash'] ?? [])->not->toHaveKey('createdApiToken');
});

it('requires a name', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.api-tokens.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect($user->tokens()->count())->toBe(0);
});

it('revokes a token the user owns', function () {
    $user = User::factory()->create();
    $token = $user->createToken('laptop')->accessToken;

    actingAs($user)
        ->delete(route('account.api-tokens.destroy', $token->getKey()))
        ->assertRedirect();

    expect($user->tokens()->count())->toBe(0);
});

it('does not revoke a token belonging to somebody else', function () {
    $user = User::factory()->create();
    $victim = User::factory()->create();
    $token = $victim->createToken('laptop')->accessToken;

    actingAs($user)
        ->delete(route('account.api-tokens.destroy', $token->getKey()))
        ->assertRedirect();

    expect($victim->tokens()->count())->toBe(1);
});

it('rejects guests', function () {
    post(route('account.api-tokens.store'), ['name' => 'laptop'])
        ->assertRedirect(route('login'));

    delete(route('account.api-tokens.destroy', '1'))
        ->assertRedirect(route('login'));
});
