<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateBrandingSettingsData;
use App\Settings\BrandingSettings;

final class UpdateBrandingSettings
{
    /**
     * Update the branding settings.
     */
    public function handle(BrandingSettings $settings, UpdateBrandingSettingsData $data): BrandingSettings
    {
        $settings->companyName = $data->companyName;
        $settings->footerText = $data->footerText;
        $settings->identityMode = $data->identityMode;
        $settings->authLayout = $data->authLayout;
        $settings->appLayout = $data->appLayout;
        $settings->colorTheme = $data->colorTheme;
        $settings->fontPair = $data->fontPair;

        $settings->save();

        return $settings;
    }
}
