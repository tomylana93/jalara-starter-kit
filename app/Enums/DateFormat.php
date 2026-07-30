<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum DateFormat: string implements HasLabel
{
    use HasOptions;

    case Iso = 'Y-m-d';
    case DayMonthYearSlashed = 'd/m/Y';
    case MonthDayYearSlashed = 'm/d/Y';
    case DayShortMonthYear = 'd M Y';

    /**
     * Get the human-readable label for the date format.
     */
    public function label(): string
    {
        return match ($this) {
            self::Iso => __('setting.date_format.iso'),
            self::DayMonthYearSlashed => __('setting.date_format.day_month_year_slashed'),
            self::MonthDayYearSlashed => __('setting.date_format.month_day_year_slashed'),
            self::DayShortMonthYear => __('setting.date_format.day_short_month_year'),
        };
    }
}
