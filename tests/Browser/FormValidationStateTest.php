<?php

use function Pest\Laravel\actingAs;

pest()->group('browser', 'form-validation-state');

it('shows backend errors on fields without native validation', function () {
    actingAs(settingsManager());

    visit(route('settings.general.edit', absolute: false))
        ->clear('#applicationName')
        ->assertScript("document.querySelector('#applicationName').checkValidity()", true)
        ->click('#dateFormat')
        ->assertSee(
            __('validation.required', ['attribute' => 'application name']),
        )
        ->assertAriaAttribute('#applicationName', 'invalid', 'true')
        ->assertNoSmoke();
});

it('removes field helpers from settings forms', function () {
    actingAs(settingsManager());

    $pages = visit([
        route('settings.general.edit', absolute: false),
        route('settings.authentication.edit', absolute: false),
        route('settings.user-provisioning.edit', absolute: false),
        route('settings.mail.edit', absolute: false),
        route('settings.security.edit', absolute: false),
        route('settings.branding.edit', absolute: false),
    ]);

    [
        $general,
        $authentication,
        $userProvisioning,
        $mail,
        $security,
        $branding,
    ] = $pages;

    $general
        ->assertDontSee(__('setting.general.help.application_name'))
        ->assertDontSee(__('setting.general.help.description'))
        ->assertDontSee(__('setting.general.help.default_locale'))
        ->assertDontSee(__('setting.general.help.date_format'));

    $authentication
        ->assertDontSee(__('setting.authentication.help.require_email_verification'))
        ->assertDontSee(__('setting.authentication.help.password_policy'))
        ->assertDontSee(__('setting.authentication.help.session_lifetime_minutes'));

    $userProvisioning
        ->assertDontSee(__('setting.user_provisioning.help.default_password'))
        ->assertDontSee(__('setting.user_provisioning.help.stored'));

    $mail
        ->assertDontSee(__('setting.mail.help.from_name'))
        ->assertDontSee(__('setting.mail.help.from_address'));

    $security
        ->assertDontSee(__('setting.security.help.max_failed_login_attempts'))
        ->assertDontSee(__('setting.security.help.suspension_duration_minutes'))
        ->assertDontSee(__('setting.security.help.maintenance_enabled'));

    $branding
        ->assertDontSee(__('setting.branding.help.company_name'))
        ->assertDontSee(__('setting.branding.help.footer_text'))
        ->assertDontSee(__('setting.branding.help.auth_layout_group'))
        ->assertDontSee(__('setting.branding.help.app_layout_group'))
        ->assertDontSee(__('setting.branding.help.color_theme_group'))
        ->assertDontSee(__('setting.branding.help.font_preset_group'));

    $pages->assertNoSmoke();
});

it('keeps email keyboard hints while validation remains on the backend', function () {
    actingAs(settingsManager());

    visit(route('settings.mail.edit', absolute: false))
        ->assertSee(__('setting.mail.help.test'))
        ->assertAttribute('#fromAddress', 'inputmode', 'email')
        ->assertAttributeMissing('#fromAddress', 'required')
        ->clear('#fromName')
        ->clear('#fromAddress')
        ->assertScript("document.querySelector('#fromAddress').checkValidity()", true)
        ->click('@update-mail-settings-button')
        ->assertSee(__('validation.required', ['attribute' => 'from name']))
        ->assertAriaAttribute('#fromName', 'invalid', 'true')
        ->assertAriaAttribute('#fromAddress', 'invalid', 'true')
        ->assertNoSmoke();
});

it('shows errors on switch and radio controls', function () {
    actingAs(settingsManager());

    $authenticationPage = visit(route('settings.authentication.edit', absolute: false));

    $authenticationPage->script(
        "document.querySelector('input[name=requireEmailVerification]').value = 'maybe'",
    );

    $authenticationPage
        ->click('@update-authentication-settings-button')
        ->assertAriaAttribute('#requireEmailVerification', 'invalid', 'true')
        ->assertNoSmoke();

    $brandingPage = visit(route('settings.branding.edit', absolute: false));

    $brandingPage->script(
        "document.querySelector('input[name=colorTheme]').value = 'not-a-theme'",
    );

    $brandingPage
        ->click('@update-branding-settings-button')
        ->assertAriaAttribute('#colorTheme-emerald', 'invalid', 'true')
        ->assertNoSmoke();
});

it('disables native validation for passwords and shows backend password errors', function () {
    actingAs(settingsManager());

    visit(route('settings.user-provisioning.edit', absolute: false))
        ->assertScript("document.querySelector('#defaultPassword').checkValidity()", true)
        ->click('@update-default-password-button')
        ->assertAriaAttribute('#defaultPassword', 'invalid', 'true')
        ->assertNoSmoke();
});
