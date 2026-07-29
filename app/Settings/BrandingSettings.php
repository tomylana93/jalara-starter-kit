<?php

namespace App\Settings;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use Spatie\LaravelSettings\Settings;

class BrandingSettings extends Settings
{
    public string $companyName;

    public ?string $footerText = null;

    public BrandingIdentityMode $identityMode;

    public ?string $logoPath = null;

    public ?string $logoDarkPath = null;

    public ?string $iconPath = null;

    public ?string $iconDarkPath = null;

    public ?string $authBackgroundPath = null;

    public AuthLayoutPreset $authLayout;

    public AppLayoutPreset $appLayout;

    public ColorThemePreset $colorTheme;

    public FontPreset $fontPreset;

    public static function group(): string
    {
        return 'branding';
    }
}
