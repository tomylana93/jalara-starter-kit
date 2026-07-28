<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum FontPreset: string implements HasLabel
{
    use HasOptions;

    case InstrumentSans = 'instrument-sans';
    case SystemSans = 'system-sans';
    case SystemSerif = 'system-serif';
    case SystemMono = 'system-mono';

    /**
     * Get the human-readable label for the preset.
     */
    public function label(): string
    {
        return match ($this) {
            self::InstrumentSans => __('setting.branding.font_preset.instrument_sans'),
            self::SystemSans => __('setting.branding.font_preset.system_sans'),
            self::SystemSerif => __('setting.branding.font_preset.system_serif'),
            self::SystemMono => __('setting.branding.font_preset.system_mono'),
        };
    }
}
