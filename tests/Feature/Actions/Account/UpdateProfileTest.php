<?php

use App\Actions\Account\UpdateProfile;
use App\Models\User;

it('updates the user name and email', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    (new UpdateProfile)->handle($user, [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);

    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe('new@example.com');
});

it('clears the email verification timestamp when the email changes', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    (new UpdateProfile)->handle($user, [
        'name' => $user->name,
        'email' => 'new@example.com',
    ]);

    expect($user->email_verified_at)->toBeNull();
});

it('preserves the email verification timestamp when the email is unchanged', function () {
    $user = User::factory()->create([
        'email' => 'unchanged@example.com',
        'email_verified_at' => now(),
    ]);
    $verifiedAt = $user->email_verified_at;

    (new UpdateProfile)->handle($user, [
        'name' => 'New Name',
        'email' => 'unchanged@example.com',
    ]);

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->email_verified_at->equalTo($verifiedAt))->toBeTrue();
});
