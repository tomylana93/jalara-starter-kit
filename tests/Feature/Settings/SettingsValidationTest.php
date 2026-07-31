<?php

use App\Http\Requests\Settings\UpdateAuthenticationSettingsRequest;
use App\Http\Requests\Settings\UpdateBrandingSettingsRequest;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Settings\UpdateMailSettingsRequest;
use App\Http\Requests\Settings\UpdateSecuritySettingsRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Validate a payload against the rules of a settings Form Request.
 *
 * @param  array<string, mixed>  $payload
 */
function settingsValidator(UpdateAuthenticationSettingsRequest|UpdateBrandingSettingsRequest|UpdateGeneralSettingsRequest|UpdateMailSettingsRequest|UpdateSecuritySettingsRequest $request, array $payload): Illuminate\Contracts\Validation\Validator
{
    return Validator::make($payload, $request->rules());
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function generalSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'applicationName' => 'Jalara',
        'description' => null,
        'defaultLocale' => 'id',
        'dateFormat' => 'd M Y',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function authenticationSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'requireEmailVerification' => true,
        'passwordPolicy' => 'standard',
        'sessionLifetimeMinutes' => 120,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mailSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'fromName' => 'Jalara Support',
        'fromAddress' => 'support@jalara.test',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function securitySettingsPayload(array $overrides = []): array
{
    return array_merge([
        'maxFailedLoginAttempts' => 5,
        'suspensionDurationMinutes' => 15,
        'maintenanceEnabled' => false,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function brandingSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'companyName' => 'Jalara Group',
        'footerText' => null,
        'identityMode' => 'icon-text',
        'authLayout' => 'simple',
        'appLayout' => 'sidebar',
        'colorTheme' => 'neutral',
        'fontPair' => 'instrument-sans',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $payload
 */
$acceptsValidSettingsPayload = function (UpdateAuthenticationSettingsRequest|UpdateBrandingSettingsRequest|UpdateGeneralSettingsRequest|UpdateMailSettingsRequest|UpdateSecuritySettingsRequest $request, array $payload): void {
    expect(settingsValidator($request, $payload)->fails())->toBeFalse();
};

it('accepts valid settings payloads', $acceptsValidSettingsPayload)->with([
    'general' => [new UpdateGeneralSettingsRequest, generalSettingsPayload()],
    'general with description' => [new UpdateGeneralSettingsRequest, generalSettingsPayload(['description' => 'Starter kit'])],
    'authentication' => [new UpdateAuthenticationSettingsRequest, authenticationSettingsPayload()],
    'authentication minimum lifetime' => [new UpdateAuthenticationSettingsRequest, authenticationSettingsPayload(['sessionLifetimeMinutes' => 5])],
    'authentication maximum lifetime' => [new UpdateAuthenticationSettingsRequest, authenticationSettingsPayload(['sessionLifetimeMinutes' => 10080])],
    'mail' => [new UpdateMailSettingsRequest, mailSettingsPayload()],
    'security' => [new UpdateSecuritySettingsRequest, securitySettingsPayload()],
    'security boundaries' => [new UpdateSecuritySettingsRequest, securitySettingsPayload([
        'maxFailedLoginAttempts' => 20,
        'suspensionDurationMinutes' => 1440,
        'maintenanceEnabled' => true,
    ])],
    'branding' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload()],
    'branding with footer' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['footerText' => 'All rights reserved.'])],
    'branding card auth layout' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['authLayout' => 'card'])],
    'branding split auth layout' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['authLayout' => 'split'])],
    'branding header app layout' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['appLayout' => 'header'])],
    'branding blue theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'blue'])],
    'branding emerald theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'emerald'])],
    'branding violet theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'violet'])],
    'branding rose theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'rose'])],
    'branding amber theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'amber'])],
    'branding teal theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'teal'])],
    'branding cyan theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'cyan'])],
    'branding indigo theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'indigo'])],
    'branding orange theme' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['colorTheme' => 'orange'])],
    'branding Space Grotesk and Inter font pair' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['fontPair' => 'space-grotesk-inter'])],
    'branding Poppins and Inter font pair' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['fontPair' => 'poppins-inter'])],
    'branding Montserrat and Open Sans font pair' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['fontPair' => 'montserrat-open-sans'])],
    'branding Playfair Display and Source Sans font pair' => [new UpdateBrandingSettingsRequest, brandingSettingsPayload(['fontPair' => 'playfair-display-source-sans'])],
]);

it('rejects invalid general settings', function (array $overrides, string $field) {
    $validator = settingsValidator(new UpdateGeneralSettingsRequest, generalSettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'missing application name' => [['applicationName' => ''], 'applicationName'],
    'long application name' => [['applicationName' => str_repeat('a', 101)], 'applicationName'],
    'long description' => [['description' => str_repeat('a', 501)], 'description'],
    'unsupported locale' => [['defaultLocale' => 'fr'], 'defaultLocale'],
    'unsupported date format' => [['dateFormat' => 'Y/m/d'], 'dateFormat'],
]);

it('rejects invalid authentication settings', function (array $overrides, string $field) {
    $validator = settingsValidator(new UpdateAuthenticationSettingsRequest, authenticationSettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'non boolean verification' => [['requireEmailVerification' => 'yes please'], 'requireEmailVerification'],
    'unknown policy' => [['passwordPolicy' => 'paranoid'], 'passwordPolicy'],
    'lifetime below minimum' => [['sessionLifetimeMinutes' => 4], 'sessionLifetimeMinutes'],
    'lifetime above maximum' => [['sessionLifetimeMinutes' => 10081], 'sessionLifetimeMinutes'],
    'non integer lifetime' => [['sessionLifetimeMinutes' => 'soon'], 'sessionLifetimeMinutes'],
]);

it('rejects invalid mail settings', function (array $overrides, string $field) {
    $validator = settingsValidator(new UpdateMailSettingsRequest, mailSettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'missing name' => [['fromName' => ''], 'fromName'],
    'long name' => [['fromName' => str_repeat('a', 101)], 'fromName'],
    'invalid address' => [['fromAddress' => 'not-an-email'], 'fromAddress'],
    'long address' => [['fromAddress' => str_repeat('a', 250).'@jalara.test'], 'fromAddress'],
]);

it('rejects invalid security settings', function (array $overrides, string $field) {
    $validator = settingsValidator(new UpdateSecuritySettingsRequest, securitySettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'attempts below minimum' => [['maxFailedLoginAttempts' => 0], 'maxFailedLoginAttempts'],
    'attempts above maximum' => [['maxFailedLoginAttempts' => 21], 'maxFailedLoginAttempts'],
    'duration below minimum' => [['suspensionDurationMinutes' => 0], 'suspensionDurationMinutes'],
    'duration above maximum' => [['suspensionDurationMinutes' => 1441], 'suspensionDurationMinutes'],
    'non boolean maintenance' => [['maintenanceEnabled' => 'later'], 'maintenanceEnabled'],
]);

it('rejects invalid branding settings', function (array $overrides, string $field) {
    $validator = settingsValidator(new UpdateBrandingSettingsRequest, brandingSettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'missing company name' => [['companyName' => ''], 'companyName'],
    'long company name' => [['companyName' => str_repeat('a', 101)], 'companyName'],
    'unknown auth layout' => [['authLayout' => 'modal'], 'authLayout'],
    'unknown app layout' => [['appLayout' => 'floating'], 'appLayout'],
    'unknown color theme' => [['colorTheme' => 'chartreuse'], 'colorTheme'],
    'unknown font pair' => [['fontPair' => 'comic'], 'fontPair'],
    'missing auth layout' => [['authLayout' => ''], 'authLayout'],
    'missing font pair' => [['fontPair' => ''], 'fontPair'],
    'long footer text' => [['footerText' => str_repeat('a', 501)], 'footerText'],
]);
