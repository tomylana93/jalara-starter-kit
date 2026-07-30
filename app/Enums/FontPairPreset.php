<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum FontPairPreset: string implements HasLabel
{
    use HasOptions;

    case InstrumentSans = 'instrument-sans';
    case SpaceGroteskInter = 'space-grotesk-inter';
    case PoppinsInter = 'poppins-inter';
    case MontserratOpenSans = 'montserrat-open-sans';
    case PlayfairDisplaySourceSans = 'playfair-display-source-sans';

    /**
     * Get the human-readable label for the preset.
     */
    public function label(): string
    {
        return match ($this) {
            self::InstrumentSans => __('setting.branding.font_pair.instrument_sans'),
            self::SpaceGroteskInter => __('setting.branding.font_pair.space_grotesk_inter'),
            self::PoppinsInter => __('setting.branding.font_pair.poppins_inter'),
            self::MontserratOpenSans => __('setting.branding.font_pair.montserrat_open_sans'),
            self::PlayfairDisplaySourceSans => __('setting.branding.font_pair.playfair_display_source_sans'),
        };
    }
}
