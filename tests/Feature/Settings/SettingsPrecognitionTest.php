<?php

use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

it('precognizes valid settings without persisting them', function (string $routeName, array $payload) {
    $settingsBefore = DB::table('settings_properties')
        ->orderBy('id')
        ->pluck('payload', 'id')
        ->all();

    actingAs(settingsManager())
        ->withPrecognition()
        ->putJson(route($routeName), $payload)
        ->assertSuccessfulPrecognition();

    $settingsAfter = DB::table('settings_properties')
        ->orderBy('id')
        ->pluck('payload', 'id')
        ->all();

    expect($settingsAfter)->toBe($settingsBefore);
})->with([
    'general' => [
        'settings.general.update',
        [
            'applicationName' => 'Jalara App',
            'description' => 'Starter kit',
            'defaultLocale' => 'id',
            'dateFormat' => 'Y-m-d',
        ],
    ],
    'authentication' => [
        'settings.authentication.update',
        [
            'requireEmailVerification' => false,
            'passwordPolicy' => 'basic',
            'sessionLifetimeMinutes' => 120,
        ],
    ],
    'user provisioning' => [
        'settings.user-provisioning.update',
        [
            'defaultPassword' => 'Jalara-Def4ult!',
            'defaultPassword_confirmation' => 'Jalara-Def4ult!',
        ],
    ],
    'mail' => [
        'settings.mail.update',
        [
            'fromName' => 'Jalara Support',
            'fromAddress' => 'support@jalara.test',
        ],
    ],
    'security' => [
        'settings.security.update',
        [
            'maxFailedLoginAttempts' => 5,
            'suspensionDurationMinutes' => 30,
            'maintenanceEnabled' => false,
        ],
    ],
    'branding' => [
        'settings.branding.update',
        [
            'companyName' => 'Jalara Group',
            'footerText' => 'All rights reserved.',
            'identityMode' => 'icon-text',
            'authLayout' => 'simple',
            'appLayout' => 'sidebar',
            'colorTheme' => 'emerald',
            'fontPair' => 'poppins-inter',
        ],
    ],
]);

it('returns field errors for selective precognitive validation', function () {
    actingAs(settingsManager())
        ->withPrecognition()
        ->withHeader('Precognition-Validate-Only', 'applicationName')
        ->putJson(route('settings.general.update'), [
            'applicationName' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('applicationName');
});
