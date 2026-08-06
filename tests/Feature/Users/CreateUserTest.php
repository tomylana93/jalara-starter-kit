<?php

use App\Actions\Settings\ForgetDefaultPassword;
use App\Actions\Users\CreateUser;
use App\Data\Users\CreateUserData;
use App\Exceptions\DefaultUserPasswordNotConfigured;
use App\Models\User;
use App\Settings\UserProvisioningSettings;
use Illuminate\Support\Facades\Hash;

/**
 * The default password configured for these tests.
 */
function defaultUserPassword(): string
{
    return 'Jalara-Def4ult!';
}

beforeEach(function () {
    $settings = app(UserProvisioningSettings::class);
    $settings->defaultPassword = defaultUserPassword();
    $settings->save();
});

it('creates verified users from the configured default password', function () {
    $user = app(CreateUser::class)->handle(new CreateUserData(
        name: 'Admin Created',
        email: 'created@example.com',
    ));

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->password)->not->toBe(defaultUserPassword())
        ->and(Hash::check(defaultUserPassword(), $user->password))->toBeTrue();
});

it('gives every created user a distinct password hash', function () {
    $action = app(CreateUser::class);

    $first = $action->handle(new CreateUserData(name: 'First', email: 'first@example.com'));
    $second = $action->handle(new CreateUserData(name: 'Second', email: 'second@example.com'));

    expect($first->password)->not->toBe($second->password);
});

it('fails safely when no default password is configured', function () {
    app(UserProvisioningSettings::class)->fill(['defaultPassword' => null])->save();

    expect(fn () => app(CreateUser::class)->handle(new CreateUserData(
        name: 'Admin Created',
        email: 'created@example.com',
    )))->toThrow(DefaultUserPasswordNotConfigured::class)
        ->and(User::query()->where('email', 'created@example.com')->exists())->toBeFalse();
});

it('never reveals the default password through the exception', function () {
    app(UserProvisioningSettings::class)->fill(['defaultPassword' => null])->save();

    try {
        app(CreateUser::class)->handle(new CreateUserData(name: 'Admin Created', email: 'created@example.com'));
    } catch (DefaultUserPasswordNotConfigured $defaultUserPasswordNotConfigured) {
        expect($defaultUserPasswordNotConfigured->getMessage())->not->toContain(defaultUserPassword());
    }
});

it('returns the existing user when the default password was removed after creation', function () {
    $action = app(CreateUser::class);
    $data = new CreateUserData(name: 'Admin Created', email: 'created@example.com');

    $user = $action->handle($data);

    app(ForgetDefaultPassword::class)->handle(app(UserProvisioningSettings::class));

    expect($action->handle($data)->is($user))->toBeTrue()
        ->and(User::query()->where('email', $data->email)->count())->toBe(1);
});

it('localizes the missing default password message', function () {
    app(UserProvisioningSettings::class)->fill(['defaultPassword' => null])->save();
    app()->setLocale('id');

    expect(fn () => app(CreateUser::class)->handle(new CreateUserData(name: 'Admin Created', email: 'created@example.com')))->toThrow(DefaultUserPasswordNotConfigured::class, __('setting.user_provisioning.default_password.not_configured', locale: 'id'));
});

it('returns the existing user when an idempotent create is retried', function () {
    $action = app(CreateUser::class);
    $data = new CreateUserData(
        name: 'Admin Created',
        email: 'created@example.com',
    );

    $firstUser = $action->handle($data);
    $originalPassword = $firstUser->password;

    $retriedUser = $action->handle($data);

    expect($retriedUser->is($firstUser))->toBeTrue()
        ->and($retriedUser->password)->toBe($originalPassword)
        ->and(User::query()->where('email', $data->email)->count())->toBe(1);
});
