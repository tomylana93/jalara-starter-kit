<?php

namespace App\Http\Presenters;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use App\Settings\BrandingSettings;
use App\Settings\SettingsResolver;

/**
 * Builds the branding payload shared with the client.
 *
 * The settings object itself is never shared: only this explicit array of
 * scalars crosses the boundary.
 */
final class BrandingPresenter
{
    /**
     * @return array{
     *     companyName: string,
     *     footerText: string|null,
     *     authLayout: string,
     *     appLayout: string,
     *     colorTheme: string,
     *     fontPreset: string,
     * }
     */
    public static function present(): array
    {
        $branding = SettingsResolver::tryResolve(BrandingSettings::class);

        if (! $branding instanceof BrandingSettings) {
            return self::defaults();
        }

        return [
            'companyName' => $branding->companyName,
            'footerText' => $branding->footerText,
            'authLayout' => $branding->authLayout->value,
            'appLayout' => $branding->appLayout->value,
            'colorTheme' => $branding->colorTheme->value,
            'fontPreset' => $branding->fontPreset->value,
        ];
    }

    /**
     * The payload used before the branding settings are persisted.
     *
     * @return array{
     *     companyName: string,
     *     footerText: string|null,
     *     authLayout: string,
     *     appLayout: string,
     *     colorTheme: string,
     *     fontPreset: string,
     * }
     */
    public static function defaults(): array
    {
        return [
            'companyName' => (string) config('app.name', 'Laravel'),
            'footerText' => null,
            'authLayout' => AuthLayoutPreset::Simple->value,
            'appLayout' => AppLayoutPreset::Sidebar->value,
            'colorTheme' => ColorThemePreset::Neutral->value,
            'fontPreset' => FontPreset::InstrumentSans->value,
        ];
    }
}
