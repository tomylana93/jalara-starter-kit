<?php

namespace App\Http\Presenters;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPairPreset;
use App\Settings\BrandingSettings;
use App\Settings\SettingsResolver;
use Illuminate\Support\Facades\Storage;

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
     *     identityMode: string,
     *     logoUrl: string|null,
     *     logoDarkUrl: string|null,
     *     iconUrl: string|null,
     *     iconDarkUrl: string|null,
     *     authBackgroundUrl: string|null,
     *     authLayout: string,
     *     appLayout: string,
     *     colorTheme: string,
     *     fontPair: string,
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
            'identityMode' => $branding->identityMode->value,
            'logoUrl' => self::url($branding->logoPath),
            'logoDarkUrl' => self::url($branding->logoDarkPath),
            'iconUrl' => self::url($branding->iconPath),
            'iconDarkUrl' => self::url($branding->iconDarkPath),
            'authBackgroundUrl' => self::url($branding->authBackgroundPath),
            'authLayout' => $branding->authLayout->value,
            'appLayout' => $branding->appLayout->value,
            'colorTheme' => $branding->colorTheme->value,
            'fontPair' => $branding->fontPair->value,
        ];
    }

    /**
     * The payload used before the branding settings are persisted.
     *
     * @return array{
     *     companyName: string,
     *     footerText: string|null,
     *     identityMode: string,
     *     logoUrl: string|null,
     *     logoDarkUrl: string|null,
     *     iconUrl: string|null,
     *     iconDarkUrl: string|null,
     *     authBackgroundUrl: string|null,
     *     authLayout: string,
     *     appLayout: string,
     *     colorTheme: string,
     *     fontPair: string,
     * }
     */
    public static function defaults(): array
    {
        $companyName = (string) config('app.name', 'Jalara');

        return [
            'companyName' => $companyName,
            'footerText' => self::defaultFooterText($companyName),
            'identityMode' => BrandingIdentityMode::IconText->value,
            'logoUrl' => null,
            'logoDarkUrl' => null,
            'iconUrl' => null,
            'iconDarkUrl' => null,
            'authBackgroundUrl' => null,
            'authLayout' => AuthLayoutPreset::Simple->value,
            'appLayout' => AppLayoutPreset::Sidebar->value,
            'colorTheme' => ColorThemePreset::Neutral->value,
            'fontPair' => FontPairPreset::InstrumentSans->value,
        ];
    }

    /**
     * The copyright line used until an administrator writes their own.
     *
     * Shared with the settings migration so a fresh install and the deployment
     * window render the same footer.
     */
    public static function defaultFooterText(string $companyName): string
    {
        return sprintf('© %s. All rights reserved.', $companyName);
    }

    /**
     * Resolve a stored path into a public URL, never exposing the path itself.
     */
    private static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
