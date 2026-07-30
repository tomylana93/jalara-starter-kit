<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum AppLayoutPreset: string implements HasLabel
{
    use HasOptions;

    case Sidebar = 'sidebar';
    case Header = 'header';

    /**
     * Get the human-readable label for the preset.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sidebar => __('setting.branding.app_layout.sidebar'),
            self::Header => __('setting.branding.app_layout.header'),
        };
    }
}
