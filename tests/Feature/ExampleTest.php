<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected from home to login', function (): void {
    $response = $this->get(route('home'));

    $response->assertRedirectToRoute('login');
});

test('authenticated users are redirected from home to dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirectToRoute('dashboard');
});
