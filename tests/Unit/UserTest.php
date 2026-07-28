<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

it('generates uuid version seven identifiers', function () {
    $identifier = (new User)->newUniqueId();

    expect(Str::isUuid($identifier, version: 7))->toBeTrue();
});

it('hashes passwords and hides sensitive attributes', function () {
    $user = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret-password',
    ]);

    expect(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->toArray())
        ->not->toHaveKeys([
            'password',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'remember_token',
        ]);
});
