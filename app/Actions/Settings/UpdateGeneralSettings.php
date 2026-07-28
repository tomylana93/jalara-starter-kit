<?php

namespace App\Actions\Settings;

use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Settings\GeneralSettings;

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

        return $settings;
    }
}
