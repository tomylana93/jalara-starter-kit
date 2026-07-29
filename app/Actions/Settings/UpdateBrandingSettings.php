<?php

namespace App\Actions\Settings;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use App\Settings\BrandingSettings;

final class UpdateBrandingSettings
{
    /**
     * Update the branding settings.
     *
     * @param  array{companyName: string, footerText: string|null, identityMode: string, authLayout: string, appLayout: string, colorTheme: string, fontPreset: string}  $attributes
     */
    public function handle(BrandingSettings $settings, array $attributes): BrandingSettings
    {
        $settings->companyName = $attributes['companyName'];
        $settings->footerText = $attributes['footerText'];
        $settings->identityMode = BrandingIdentityMode::from($attributes['identityMode']);
        $settings->authLayout = AuthLayoutPreset::from($attributes['authLayout']);
        $settings->appLayout = AppLayoutPreset::from($attributes['appLayout']);
        $settings->colorTheme = ColorThemePreset::from($attributes['colorTheme']);
        $settings->fontPreset = FontPreset::from($attributes['fontPreset']);

        $settings->save();

        return $settings;
    }
}
