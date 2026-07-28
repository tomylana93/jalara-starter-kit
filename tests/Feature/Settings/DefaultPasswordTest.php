<?php

use App\Actions\Settings\ForgetDefaultPassword;
use App\Actions\Settings\UpdateDefaultPassword;
use App\Enums\PasswordPolicy;
use App\Http\Requests\Settings\UpdateAuthenticationSettingsRequest;
use App\Http\Requests\Settings\UpdateDefaultPasswordRequest;
use App\Settings\UserProvisioningSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Validate a payload against the rules of a settings Form Request.
 *
 * @param  class-string<FormRequest>  $request
 * @param  array<string, mixed>  $payload
 */
function defaultPasswordValidator(string $request, array $payload): Illuminate\Contracts\Validation\Validator
{
    return Validator::make($payload, (new $request)->rules());
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function authenticationPayload(array $overrides = []): array
{
    return array_merge([
        'requireEmailVerification' => true,
        'passwordPolicy' => 'standard',
        'sessionLifetimeMinutes' => 120,
    ], $overrides);
}

function storeDefaultPassword(string $password): void
{
    app(UpdateDefaultPassword::class)->handle(app(UserProvisioningSettings::class), $password);
}

/**
 * The raw persisted payload of a settings property.
 */
function settingsPayload(string $group, string $name): string
{
    return (string) DB::table('settings_properties')
        ->where('group', $group)
        ->where('name', $name)
        ->value('payload');
}

it('stores and reads back the default password', function () {
    storeDefaultPassword('Jalara-Def4ult!');

    expect(app(UserProvisioningSettings::class)->refresh()->defaultPassword)->toBe('Jalara-Def4ult!');
});

it('never persists the default password in plaintext', function () {
    storeDefaultPassword('Jalara-Def4ult!');

    $payload = settingsPayload('user_provisioning', 'defaultPassword');

    expect($payload)->not->toBeEmpty()
        ->and($payload)->not->toContain('Jalara-Def4ult!');
});

it('leaves the default password out of the settings representation', function () {
    storeDefaultPassword('Jalara-Def4ult!');

    $representation = app(UserProvisioningSettings::class)->refresh();

    expect($representation->toArray())->not->toHaveKey('defaultPassword')
        ->and($representation->toArray()['hasDefaultPassword'])->toBeTrue()
        ->and($representation->toJson())->not->toContain('Jalara-Def4ult!');
});

it('reports whether a default password is configured', function () {
    expect(app(UserProvisioningSettings::class)->hasDefaultPassword())->toBeFalse();

    storeDefaultPassword('Jalara-Def4ult!');

    expect(app(UserProvisioningSettings::class)->refresh()->hasDefaultPassword())->toBeTrue();
});

it('requires a confirmed password that satisfies the active policy', function (array $payload, bool $passes) {
    usePasswordPolicy(PasswordPolicy::Strict);

    $validator = defaultPasswordValidator(UpdateDefaultPasswordRequest::class, $payload);

    expect($validator->passes())->toBe($passes);
})->with([
    'valid' => [['defaultPassword' => 'Jalara-Def4ult!', 'defaultPassword_confirmation' => 'Jalara-Def4ult!'], true],
    'missing' => [['defaultPassword' => '', 'defaultPassword_confirmation' => ''], false],
    'unconfirmed' => [['defaultPassword' => 'Jalara-Def4ult!', 'defaultPassword_confirmation' => 'Other-P4ssword!'], false],
    'too weak for the policy' => [['defaultPassword' => 'password', 'defaultPassword_confirmation' => 'password'], false],
]);

it('keeps the stored password when the form input is empty', function () {
    storeDefaultPassword('Jalara-Def4ult!');

    $validator = defaultPasswordValidator(UpdateDefaultPasswordRequest::class, [
        'defaultPassword' => '',
        'defaultPassword_confirmation' => '',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and(app(UserProvisioningSettings::class)->refresh()->defaultPassword)->toBe('Jalara-Def4ult!');
});

it('removes the default password only through the explicit operation', function () {
    storeDefaultPassword('Jalara-Def4ult!');

    app(ForgetDefaultPassword::class)->handle(app(UserProvisioningSettings::class));

    expect(app(UserProvisioningSettings::class)->refresh()->defaultPassword)->toBeNull();
});

it('rejects a policy the stored default password would not satisfy', function () {
    storeDefaultPassword('simplepass');

    $validator = defaultPasswordValidator(UpdateAuthenticationSettingsRequest::class, authenticationPayload([
        'passwordPolicy' => PasswordPolicy::Strict->value,
    ]));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('passwordPolicy');
});

it('accepts a policy the stored default password satisfies', function () {
    storeDefaultPassword('Jalara-Def4ult!');

    $validator = defaultPasswordValidator(UpdateAuthenticationSettingsRequest::class, authenticationPayload([
        'passwordPolicy' => PasswordPolicy::Strict->value,
    ]));

    expect($validator->passes())->toBeTrue();
});

it('allows any policy while no default password is configured', function () {
    $validator = defaultPasswordValidator(UpdateAuthenticationSettingsRequest::class, authenticationPayload([
        'passwordPolicy' => PasswordPolicy::Strict->value,
    ]));

    expect($validator->passes())->toBeTrue();
});
