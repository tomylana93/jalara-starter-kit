<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;

it('logs out disabled and suspended sessions on the next request', function (string $state) {
    $user = User::factory()->{$state}()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');

    assertGuest();
})->with([
    'disabled',
    'suspended',
]);

it('returns forbidden JSON and logs out blocked sessions', function () {
    $user = User::factory()->disabled()->create();

    actingAs($user)
        ->getJson(route('api.v1.me'))
        ->assertForbidden()
        ->assertJsonPath('message', __('auth.login.message.disabled'));

    assertGuest();
});

it('reactivates an expired suspension for an existing session', function () {
    $user = User::factory()->suspended(now()->subMinute())->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect($user->refresh()->status)->toBe(UserStatus::Active)
        ->and($user->suspended_until)->toBeNull();
});

it('redirects forced password changes to the security flow', function () {
    $user = User::factory()->mustChangePassword()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('account.security.edit');

    actingAs($user)
        ->getJson(route('api.v1.me'))
        ->assertStatus(409)
        ->assertJsonPath('message', __('auth.login.message.must_change_password'));
});

it('allows password confirmation, security, password update, and logout during a forced change', function () {
    $user = User::factory()->mustChangePassword()->create();

    actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Security')
            ->where('mustChangePassword', true),
        );

    actingAs($user)
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirectToRoute('dashboard');

    expect($user->refresh()->must_change_password)->toBeFalse()
        ->and(Hash::check('new-password', $user->password))->toBeTrue();

    actingAs(User::factory()->mustChangePassword()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));
});
