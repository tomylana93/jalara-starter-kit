<?php

namespace App\Providers;

use App\Settings\AuthenticationSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SettingsResolver;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

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

        /*
         * Settings migrations write straight to the repository, so the package
         * never emits `SettingsSaved` for them and a cached group would survive
         * a deployment that changed it.
         *
         * The event only fires when the run had migrations to apply, which
         * covers the repo's own path - settings changes ship as new migrations.
         * Any other direct write to the table (a seeder, an edited migration
         * that already ran) still needs `settings:clear-cache`.
         */
        Event::listen(MigrationsEnded::class, function (): void {
            $this->flushSettingsCache();
        });
    }

    /**
     * Drop every cached settings group, ignoring the caches that are disabled.
     */
    private function flushSettingsCache(): void
    {
        foreach (app(SettingsCacheFactory::class)->all() as $cache) {
            if ($cache->isEnabled()) {
                $cache->clear();
            }
        }
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
     * Apply the application name, description, and locale.
     */
    private function applyGeneralSettings(bool $refresh): void
    {
        $general = $this->resolve(GeneralSettings::class, $refresh);

        if (! $general instanceof GeneralSettings) {
            return;
        }

        Config::set('app.name', $general->applicationName);
        Config::set('app.description', $general->description);
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
