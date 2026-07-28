<?php

namespace App\Providers;

use App\Settings\AuthenticationSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SettingsResolver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\Settings;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Apply the persisted application settings to the runtime configuration.
     */
    public function boot(): void
    {
        $this->applySettings();

        /*
         * Long running workers boot once, so re-read the settings before every
         * job. Queued mail and notifications would otherwise keep using the
         * values that were persisted when the worker started.
         */
        Queue::looping(function (): void {
            $this->applySettings(refresh: true);
        });
    }

    /**
     * Apply every settings group to the runtime configuration.
     */
    private function applySettings(bool $refresh = false): void
    {
        $this->applyGeneralSettings($refresh);
        $this->applyAuthenticationSettings($refresh);
        $this->applyMailSettings($refresh);
    }

    /**
     * Apply the application name and locale.
     */
    private function applyGeneralSettings(bool $refresh): void
    {
        $general = $this->resolve(GeneralSettings::class, $refresh);

        if (! $general instanceof GeneralSettings) {
            return;
        }

        Config::set('app.name', $general->applicationName);
        Config::set('app.locale', $general->defaultLocale->value);

        $this->app->setLocale($general->defaultLocale->value);
    }

    /**
     * Apply the session lifetime.
     */
    private function applyAuthenticationSettings(bool $refresh): void
    {
        $authentication = $this->resolve(AuthenticationSettings::class, $refresh);

        if (! $authentication instanceof AuthenticationSettings) {
            return;
        }

        Config::set('session.lifetime', $authentication->sessionLifetimeMinutes);
    }

    /**
     * Apply the mail sender identity.
     */
    private function applyMailSettings(bool $refresh): void
    {
        $mail = $this->resolve(MailSettings::class, $refresh);

        if (! $mail instanceof MailSettings) {
            return;
        }

        Config::set('mail.from.name', $mail->fromName);
        Config::set('mail.from.address', $mail->fromAddress);
    }

    /**
     * Resolve a settings group, optionally re-reading it from the repository.
     *
     * @template TSettings of Settings
     *
     * @param  class-string<TSettings>  $settings
     * @return TSettings|null
     */
    private function resolve(string $settings, bool $refresh): ?Settings
    {
        $resolved = SettingsResolver::tryResolve($settings);

        if ($refresh && $resolved instanceof Settings) {
            $resolved->refresh();
        }

        return $resolved;
    }
}
