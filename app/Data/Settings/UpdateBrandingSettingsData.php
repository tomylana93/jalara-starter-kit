<?php

namespace App\Data\Settings;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\BrandingIdentityMode;
use App\Enums\ColorThemePreset;
use App\Enums\FontPairPreset;

final readonly class UpdateBrandingSettingsData
{
    public function __construct(
        public string $companyName,
        public ?string $footerText,
        public BrandingIdentityMode $identityMode,
        public AuthLayoutPreset $authLayout,
        public AppLayoutPreset $appLayout,
        public ColorThemePreset $colorTheme,
        public FontPairPreset $fontPair,
    ) {}
}
