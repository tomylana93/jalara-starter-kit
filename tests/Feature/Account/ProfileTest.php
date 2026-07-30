<?php

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\from;

it('displays the profile page with account disable ability', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->get(route('account.profile.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('account/Profile')
        ->where('canDisableAccount', true),
    );
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

it('localizes the profile update message', function () {
    app()->setLocale('id');

    $user = User::factory()->create();

    actingAs($user)
        ->patch(route('account.profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Profil berhasil diperbarui.',
        ]);
});

it('precognizes valid profile information without updating the user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->withPrecognition()
        ->patchJson(route('account.profile.update'), [
            'name' => 'Precognitive User',
            'email' => 'precognitive@example.com',
        ])
        ->assertSuccessfulPrecognition();

    $user->refresh();

    expect($user->name)->not->toBe('Precognitive User');
    expect($user->email)->not->toBe('precognitive@example.com');
    expect($user->email_verified_at)->not->toBeNull();
});

it('precognizes that another user email is unavailable', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'email')
        ->patchJson(route('account.profile.update'), [
            'email' => $otherUser->email,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($user->refresh()->email)->not->toBe($otherUser->email);
});

it('precognizes that the current user email remains available', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'email')
        ->patchJson(route('account.profile.update'), [
            'email' => $user->email,
        ])
        ->assertSuccessfulPrecognition();
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

it('allows a user to disable their account while preserving its role', function () {
    $user = User::factory()->create();
    $role = Spatie\Permission\Models\Role::findOrCreate(Role::User->value, 'web');
    $user->assignRole($role);

    $response = actingAs($user)
        ->patch(route('account.disable'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    assertGuest();
    expect($user->refresh()->status)->toBe(UserStatus::Disabled)
        ->and($user->hasRole(Role::User->value))->toBeTrue();
});

it('requires the correct password to disable account', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = from(route('account.profile.edit'))
        ->patch(route('account.disable'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('account.profile.edit'));

    expect($user->refresh()->status)->toBe(UserStatus::Active);
    $this->assertAuthenticatedAs($user);
});

it('forbids system users and super administrators from disabling their accounts', function (string $protectedBy) {
    Spatie\Permission\Models\Role::findOrCreate(Role::SuperAdmin->value, 'web');
    $user = User::factory()->create([
        'is_system' => $protectedBy === 'system flag',
    ]);

    if ($protectedBy === 'super-admin role') {
        $user->assignRole(Role::SuperAdmin->value);
    }

    actingAs($user)
        ->patch(route('account.disable'), ['password' => 'password'])
        ->assertForbidden();

    expect($user->refresh()->status)->toBe(UserStatus::Active);
    $this->assertAuthenticatedAs($user);

    actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('canDisableAccount', false));
})->with(['system flag', 'super-admin role']);

it('requires authentication for account routes', function () {
    $this->get(route('account.index'))->assertRedirect(route('login'));
    $this->get(route('account.profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('account.profile.update'))->assertRedirect(route('login'));
    $this->patch(route('account.disable'))->assertRedirect(route('login'));
});
