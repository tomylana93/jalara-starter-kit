<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests to the login page', function () {
    $response = get(route('home'));

    $response->assertRedirectToRoute('login');
});

it('redirects authenticated users to the dashboard', function () {
    actingAs(User::factory()->create());

    $response = get(route('home'));

    $response->assertRedirectToRoute('dashboard');
});
