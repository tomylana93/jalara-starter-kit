<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\from;

it('displays the profile page', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->get(route('account.profile.edit'));

    $response->assertOk();
});

it('updates profile information', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.profile.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Profile updated.']);

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

it('leaves email verification status unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('allows a user to delete their account', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->delete(route('account.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    assertGuest();
    expect($user->fresh())->toBeNull();
});

it('requires the correct password to delete account', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = from(route('account.profile.edit'))
        ->delete(route('account.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('account.profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

it('requires authentication for account routes', function () {
    $this->get(route('account.index'))->assertRedirect(route('login'));
    $this->get(route('account.profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('account.profile.update'))->assertRedirect(route('login'));
    $this->delete(route('account.destroy'))->assertRedirect(route('login'));
});

it('no longer serves the legacy settings endpoints', function () {
    $this->get('/settings')->assertNotFound();
    $this->get('/settings/profile')->assertNotFound();
    $this->get('/settings/security')->assertNotFound();
    $this->get('/settings/appearance')->assertNotFound();
});

it('no longer registers the legacy settings route names', function () {
    expect(Route::has('profile.edit'))->toBeFalse();
    expect(Route::has('profile.update'))->toBeFalse();
    expect(Route::has('profile.destroy'))->toBeFalse();
    expect(Route::has('security.edit'))->toBeFalse();
    expect(Route::has('user-password.update'))->toBeFalse();
    expect(Route::has('appearance.edit'))->toBeFalse();
});
