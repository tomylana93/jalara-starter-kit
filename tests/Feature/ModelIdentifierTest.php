<?php

use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('gives users a uuid version seven identifier', function () {
    $user = User::factory()->create();

    expect($user->getKey())
        ->toBeString()
        ->and(Str::isUuid($user->getKey(), version: 7))
        ->toBeTrue();
});

it('finds users by their uuid identifier', function () {
    $user = User::factory()->create();

    expect($user->is(User::find($user->getKey())))->toBeTrue();
});

it('authenticates users with uuid identifiers', function () {
    $user = User::factory()->create();

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('stores the uuid user identifier on the database session record', function () {
    config(['session.driver' => 'database']);

    $user = User::factory()->create();

    actingAs($user);
    get(route('dashboard'))->assertOk();

    assertDatabaseHas('sessions', [
        'user_id' => $user->getKey(),
    ]);
});
