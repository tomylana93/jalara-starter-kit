<?php

namespace App\Actions\Settings;

use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Config;

final class UpdateGeneralSettings
{
    /**
     * Update the general settings.
     *
     * @param  array{applicationName: string, description: string|null, defaultLocale: string, dateFormat: string}  $attributes
     */
    public function handle(GeneralSettings $settings, array $attributes): GeneralSettings
    {
        $settings->applicationName = $attributes['applicationName'];
        $settings->description = $attributes['description'];
        $settings->defaultLocale = Locale::from($attributes['defaultLocale']);
        $settings->dateFormat = DateFormat::from($attributes['dateFormat']);

        $settings->save();

        Config::set('app.name', $settings->applicationName);
        Config::set('app.locale', $settings->defaultLocale->value);
        app()->setLocale($settings->defaultLocale->value);

        return $settings;
    }
}
