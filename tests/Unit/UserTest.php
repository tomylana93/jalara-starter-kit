<?php

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonInterface;
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
            'phone',
            'status',
            'must_change_password',
            'last_login_at',
            'failed_login_attempts',
            'suspended_until',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'remember_token',
        ]);
});

it('uses database-aligned defaults and casts internal authentication metadata', function () {
    $user = (new User)->forceFill([
        'last_login_at' => '2026-07-28 10:00:00',
        'suspended_until' => '2026-07-28 10:15:00',
    ]);

    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->failed_login_attempts)->toBe(0)
        ->and($user->last_login_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($user->suspended_until)->toBeInstanceOf(CarbonInterface::class);
});
