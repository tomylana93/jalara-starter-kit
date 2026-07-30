<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum BrandingIdentityMode: string implements HasLabel
{
    use HasOptions;

    case Logo = 'logo';
    case IconText = 'icon-text';

    /**
     * Get the human-readable label for the identity mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::Logo => __('setting.branding.identity_mode.logo'),
            self::IconText => __('setting.branding.identity_mode.icon_text'),
        };
    }
}
