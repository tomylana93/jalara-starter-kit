<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum Locale: string implements HasLabel
{
    use HasOptions;

    case English = 'en';
    case Indonesian = 'id';

    /**
     * Get the human-readable label for the locale.
     */
    public function label(): string
    {
        return match ($this) {
            self::English => __('setting.locale.en'),
            self::Indonesian => __('setting.locale.id'),
        };
    }

    /**
     * Get the supported locale values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
