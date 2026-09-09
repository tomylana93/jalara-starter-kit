<?php

use App\Models\MasterData\User;

it('redirects guests to the login page', function (): void {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

it('redirects authenticated users to the dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});
