<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateGeneralSettingsData;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Config;

final class UpdateGeneralSettings
{
    /**
     * Update the general settings.
     */
    public function handle(GeneralSettings $settings, UpdateGeneralSettingsData $data): GeneralSettings
    {
        $settings->applicationName = $data->applicationName;
        $settings->description = $data->description;
        $settings->defaultLocale = $data->defaultLocale;
        $settings->dateFormat = $data->dateFormat;

        $settings->save();

        Config::set('app.name', $settings->applicationName);
        Config::set('app.description', $settings->description);
        Config::set('app.locale', $settings->defaultLocale->value);
        app()->setLocale($settings->defaultLocale->value);

        return $settings;
    }
}
