<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the confirm password screen', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = get(route('password.confirm'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/ConfirmPassword'),
    );
});

it('requires authentication for password confirmation', function () {
    $response = get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});
