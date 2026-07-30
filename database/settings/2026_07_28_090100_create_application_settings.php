<?php

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\DateFormat;
use App\Enums\FontPairPreset;
use App\Enums\Locale;
use App\Enums\PasswordPolicy;
use App\Http\Presenters\BrandingPresenter;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $applicationName = (string) config('app.name', 'Jalara');

        $this->migrator->add('general.applicationName', $applicationName);
        $this->migrator->add('general.description', (string) config('app.description', $applicationName));
        $this->migrator->add('general.defaultLocale', $this->supportedLocale());
        $this->migrator->add('general.dateFormat', DateFormat::DayShortMonthYear->value);

        $this->migrator->add('authentication.requireEmailVerification', true);
        $this->migrator->add('authentication.passwordPolicy', PasswordPolicy::Strict->value);
        $this->migrator->add('authentication.sessionLifetimeMinutes', 120);

        $this->migrator->add('mail.fromName', (string) config('mail.from.name', $applicationName));
        $this->migrator->add('mail.fromAddress', (string) config('mail.from.address', 'hello@jalara.dev'));

        /*
         * Configured by an administrator through the settings screen, never
         * seeded, and stored encrypted.
         */
        $this->migrator->addEncrypted('user_provisioning.defaultPassword', null);

        $this->migrator->add('security.maxFailedLoginAttempts', 5);
        $this->migrator->add('security.suspensionDurationMinutes', 15);
        $this->migrator->add('security.maintenanceEnabled', false);

        $this->migrator->add('branding.companyName', $applicationName);
        $this->migrator->add('branding.footerText', BrandingPresenter::defaultFooterText($applicationName));
        $this->migrator->add('branding.authLayout', AuthLayoutPreset::Simple->value);
        $this->migrator->add('branding.appLayout', AppLayoutPreset::Sidebar->value);
        $this->migrator->add('branding.colorTheme', ColorThemePreset::Neutral->value);
        $this->migrator->add('branding.fontPair', FontPairPreset::InstrumentSans->value);
        $this->migrator->add('branding.identityMode', BrandingIdentityMode::IconText->value);
        $this->migrator->add('branding.logoPath', null);
        $this->migrator->add('branding.logoDarkPath', null);
        $this->migrator->add('branding.iconPath', null);
        $this->migrator->add('branding.iconDarkPath', null);
        $this->migrator->add('branding.authBackgroundPath', null);
    }

    public function down(): void
    {
        foreach ([
            'general.applicationName',
            'general.description',
            'general.defaultLocale',
            'general.dateFormat',
            'authentication.requireEmailVerification',
            'authentication.passwordPolicy',
            'authentication.sessionLifetimeMinutes',
            'mail.fromName',
            'mail.fromAddress',
            'user_provisioning.defaultPassword',
            'security.maxFailedLoginAttempts',
            'security.suspensionDurationMinutes',
            'security.maintenanceEnabled',
            'branding.companyName',
            'branding.footerText',
            'branding.authLayout',
            'branding.appLayout',
            'branding.colorTheme',
            'branding.fontPair',
            'branding.identityMode',
            'branding.logoPath',
            'branding.logoDarkPath',
            'branding.iconPath',
            'branding.iconDarkPath',
            'branding.authBackgroundPath',
        ] as $property) {
            $this->migrator->deleteIfExists($property);
        }
    }

    private function supportedLocale(): string
    {
        $locale = (string) config('app.locale', Locale::English->value);

        return Locale::tryFrom($locale) instanceof Locale
            ? $locale
            : Locale::English->value;
    }
};
