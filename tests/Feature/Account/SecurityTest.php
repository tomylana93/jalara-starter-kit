<?php

use App\Enums\PasswordPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;

it('updates the password', function () {
    usePasswordPolicy(PasswordPolicy::Strict);

    $user = User::factory()->create();

    actingAs($user);

    $response = from(route('account.security.edit'))
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'Jalara-Str0ng!',
            'password_confirmation' => 'Jalara-Str0ng!',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Password updated.']);

    expect(Hash::check('Jalara-Str0ng!', $user->refresh()->password))->toBeTrue();
});

it('rejects a password that does not satisfy the strict policy', function () {
    usePasswordPolicy(PasswordPolicy::Strict);

    $user = User::factory()->create();

    actingAs($user);

    from(route('account.security.edit'))
        ->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('password');
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
