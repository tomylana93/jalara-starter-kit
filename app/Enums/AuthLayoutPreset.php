<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum AuthLayoutPreset: string implements HasLabel
{
    use HasOptions;

    case Simple = 'simple';
    case Card = 'card';
    case Split = 'split';

    /**
     * Get the human-readable label for the preset.
     */
    public function label(): string
    {
        return match ($this) {
            self::Simple => __('setting.branding.auth_layout.simple'),
            self::Card => __('setting.branding.auth_layout.card'),
            self::Split => __('setting.branding.auth_layout.split'),
        };
    }
}
