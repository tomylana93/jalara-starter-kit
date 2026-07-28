<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;

it('displays the security page', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Security')
            ->where('mustChangePassword', false)
            ->where('canManageTwoFactor', true)
            ->where('twoFactorEnabled', false),
        );
});

it('requires password confirmation for the security page when enabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->create();

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $response = actingAs($user)
        ->get(route('account.security.edit'));

    $response->assertRedirect(route('password.confirm'));
});

it('renders the security page without two factor when the feature is disabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    config(['fortify.features' => []]);

    $user = User::factory()->create();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Security')
            ->where('mustChangePassword', false)
            ->where('canManageTwoFactor', false)
            ->missing('twoFactorEnabled')
            ->missing('requiresConfirmation'),
        );
});

it('updates the password', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = from(route('account.security.edit'))
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Password updated.']);

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('localizes the password update message', function () {
    app()->setLocale('id');

    $user = User::factory()->create();

    actingAs($user);

    from(route('account.security.edit'))
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Password berhasil diperbarui.',
        ]);
});

it('requires the correct password to update the password', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = from(route('account.security.edit'))
        ->put(route('account.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('account.security.edit'));
});

it('requires authentication for security routes', function () {
    $this->get(route('account.security.edit'))->assertRedirect(route('login'));
    $this->put(route('account.password.update'))->assertRedirect(route('login'));
    $this->get(route('account.appearance.edit'))->assertRedirect(route('login'));
});

it('throttles password update attempts', function () {
    $user = User::factory()->create();

    actingAs($user);

    foreach (range(1, 6) as $_) {
        $this->put(route('account.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
    }

    $response = $this->put(route('account.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertTooManyRequests();
});
