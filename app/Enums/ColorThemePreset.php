<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum ColorThemePreset: string implements HasLabel
{
    use HasOptions;

    case Neutral = 'neutral';
    case Blue = 'blue';
    case Emerald = 'emerald';
    case Violet = 'violet';
    case Rose = 'rose';
    case Amber = 'amber';

    /**
     * Get the human-readable label for the preset.
     */
    public function label(): string
    {
        return match ($this) {
            self::Neutral => __('setting.branding.color_theme.neutral'),
            self::Blue => __('setting.branding.color_theme.blue'),
            self::Emerald => __('setting.branding.color_theme.emerald'),
            self::Violet => __('setting.branding.color_theme.violet'),
            self::Rose => __('setting.branding.color_theme.rose'),
            self::Amber => __('setting.branding.color_theme.amber'),
        };
    }
}
