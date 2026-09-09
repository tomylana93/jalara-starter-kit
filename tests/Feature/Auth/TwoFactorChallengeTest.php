<?php

use App\Models\MasterData\User;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

beforeEach(function (): void {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
});

it('two factor challenge redirects to login when not authenticated', function (): void {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

it('two factor challenge can be rendered', function (): void {
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page
            ->component('auth/TwoFactorChallenge'),
        );
});
