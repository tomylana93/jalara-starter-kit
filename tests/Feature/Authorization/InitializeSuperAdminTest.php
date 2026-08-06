<?php

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;

beforeEach(function () {
    config(['superadmin' => [
        'name' => 'Root Operator',
        'email' => 'root@example.com',
        'phone' => null,
        'email_verified' => true,
        'password' => 'initial-password',
    ]]);
});

it('creates restores and enforces the sole super admin role', function () {
    pendingCommand(artisan('auth:init-superadmin'))->assertSuccessful();
    $user = User::query()->where('is_system', true)->sole();
    $originalPassword = $user->password;
    $user->forceFill(['status' => UserStatus::Disabled])->save();
    $user->assignRole(Role::User->value);

    config(['superadmin.name' => 'Updated Root', 'superadmin.password' => 'ignored-password']);
    pendingCommand(artisan('auth:init-superadmin'))->assertSuccessful();

    expect($user->refresh()->name)->toBe('Updated Root')
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->getRoleNames()->all())->toBe([Role::SuperAdmin->value])
        ->and($user->password)->toBe($originalPassword);
});

it('resets the super admin password only when requested', function () {
    pendingCommand(artisan('auth:init-superadmin'))->assertSuccessful();
    config(['superadmin.password' => 'replacement-password']);

    pendingCommand(artisan('auth:init-superadmin', ['--reset-password' => true]))->assertSuccessful();

    expect(Hash::check('replacement-password', User::query()->where('is_system', true)->sole()->password))->toBeTrue();
});

it('fails without changes for invalid config email conflicts and multiple system users', function (string $scenario) {
    if ($scenario === 'invalid config') {
        config(['superadmin.email' => null]);
    } elseif ($scenario === 'email conflict') {
        User::factory()->create(['email' => 'root@example.com']);
    } else {
        User::factory()->count(2)->create(['is_system' => true]);
    }

    $before = User::query()->count();
    pendingCommand(artisan('auth:init-superadmin'))->assertFailed();

    expect(User::query()->count())->toBe($before)
        ->and(User::query()->where('is_system', true)->whereHas('roles')->count())->toBe(0);
})->with(['invalid config', 'email conflict', 'multiple system users']);
