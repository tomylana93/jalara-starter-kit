<?php

use App\Actions\Users\CreateUser;
use App\Models\User;

it('creates verified users for administrative CRUD operations', function () {
    $user = app(CreateUser::class)->handle([
        'name' => 'Admin Created',
        'email' => 'created@example.com',
        'password' => 'password',
    ]);

    expect($user)
        ->toBeInstanceOf(User::class)
        ->hasVerifiedEmail()->toBeTrue();
});

it('returns the existing user when an idempotent create is retried', function () {
    $action = app(CreateUser::class);
    $attributes = [
        'name' => 'Admin Created',
        'email' => 'created@example.com',
        'password' => 'password',
    ];

    $firstUser = $action->handle($attributes);
    $retriedUser = $action->handle($attributes);

    expect($retriedUser->is($firstUser))->toBeTrue()
        ->and(User::query()->where('email', $attributes['email'])->count())->toBe(1);
});
