<?php

use App\Http\Requests\Settings\UpdateAuthenticationSettingsRequest;
use App\Http\Requests\Settings\UpdateBrandingSettingsRequest;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Settings\UpdateMailSettingsRequest;
use App\Http\Requests\Settings\UpdateSecuritySettingsRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Validate a payload against the rules of a settings Form Request.
 *
 * @param  class-string<FormRequest>  $request
 * @param  array<string, mixed>  $payload
 */
function settingsValidator(string $request, array $payload): Illuminate\Contracts\Validation\Validator
{
    return Validator::make($payload, (new $request)->rules());
}

/**
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
 * @return array<string, mixed>
 */
function brandingSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'companyName' => 'Jalara Group',
        'footerText' => null,
        'authLayout' => 'simple',
        'appLayout' => 'sidebar',
        'colorTheme' => 'neutral',
        'fontPreset' => 'instrument-sans',
    ], $overrides);
}

it('accepts valid settings payloads', function (string $request, array $payload) {
    expect(settingsValidator($request, $payload)->passes())->toBeTrue();
})->with([
    'general' => [UpdateGeneralSettingsRequest::class, generalSettingsPayload()],
    'general with description' => [UpdateGeneralSettingsRequest::class, generalSettingsPayload(['description' => 'Starter kit'])],
    'authentication' => [UpdateAuthenticationSettingsRequest::class, authenticationSettingsPayload()],
    'authentication minimum lifetime' => [UpdateAuthenticationSettingsRequest::class, authenticationSettingsPayload(['sessionLifetimeMinutes' => 5])],
    'authentication maximum lifetime' => [UpdateAuthenticationSettingsRequest::class, authenticationSettingsPayload(['sessionLifetimeMinutes' => 10080])],
    'mail' => [UpdateMailSettingsRequest::class, mailSettingsPayload()],
    'security' => [UpdateSecuritySettingsRequest::class, securitySettingsPayload()],
    'security boundaries' => [UpdateSecuritySettingsRequest::class, securitySettingsPayload([
        'maxFailedLoginAttempts' => 20,
        'suspensionDurationMinutes' => 1440,
        'maintenanceEnabled' => true,
    ])],
    'branding' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload()],
    'branding with footer' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['footerText' => 'All rights reserved.'])],
    'branding card auth layout' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['authLayout' => 'card'])],
    'branding split auth layout' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['authLayout' => 'split'])],
    'branding header app layout' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['appLayout' => 'header'])],
    'branding blue theme' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['colorTheme' => 'blue'])],
    'branding emerald theme' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['colorTheme' => 'emerald'])],
    'branding violet theme' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['colorTheme' => 'violet'])],
    'branding rose theme' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['colorTheme' => 'rose'])],
    'branding amber theme' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['colorTheme' => 'amber'])],
    'branding system sans font' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['fontPreset' => 'system-sans'])],
    'branding system serif font' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['fontPreset' => 'system-serif'])],
    'branding system mono font' => [UpdateBrandingSettingsRequest::class, brandingSettingsPayload(['fontPreset' => 'system-mono'])],
]);

it('rejects invalid general settings', function (array $overrides, string $field) {
    $validator = settingsValidator(UpdateGeneralSettingsRequest::class, generalSettingsPayload($overrides));

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
    $validator = settingsValidator(UpdateAuthenticationSettingsRequest::class, authenticationSettingsPayload($overrides));

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
    $validator = settingsValidator(UpdateMailSettingsRequest::class, mailSettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'missing name' => [['fromName' => ''], 'fromName'],
    'long name' => [['fromName' => str_repeat('a', 101)], 'fromName'],
    'invalid address' => [['fromAddress' => 'not-an-email'], 'fromAddress'],
    'long address' => [['fromAddress' => str_repeat('a', 250).'@jalara.test'], 'fromAddress'],
]);

it('rejects invalid security settings', function (array $overrides, string $field) {
    $validator = settingsValidator(UpdateSecuritySettingsRequest::class, securitySettingsPayload($overrides));

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
    $validator = settingsValidator(UpdateBrandingSettingsRequest::class, brandingSettingsPayload($overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with([
    'missing company name' => [['companyName' => ''], 'companyName'],
    'long company name' => [['companyName' => str_repeat('a', 101)], 'companyName'],
    'unknown auth layout' => [['authLayout' => 'modal'], 'authLayout'],
    'unknown app layout' => [['appLayout' => 'floating'], 'appLayout'],
    'unknown color theme' => [['colorTheme' => 'chartreuse'], 'colorTheme'],
    'unknown font preset' => [['fontPreset' => 'comic'], 'fontPreset'],
    'missing auth layout' => [['authLayout' => ''], 'authLayout'],
    'missing font preset' => [['fontPreset' => ''], 'fontPreset'],
    'long footer text' => [['footerText' => str_repeat('a', 501)], 'footerText'],
]);
