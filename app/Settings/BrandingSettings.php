<?php

namespace App\Settings;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use Spatie\LaravelSettings\Settings;

class BrandingSettings extends Settings
{
    public string $companyName;

    public ?string $footerText = null;

    public AuthLayoutPreset $authLayout;

    public AppLayoutPreset $appLayout;

    public ColorThemePreset $colorTheme;

    public FontPreset $fontPreset;

    public static function group(): string
    {
        return 'branding';
    }
}
