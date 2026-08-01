<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum DocumentationStatus: string implements HasLabel
{
    use HasOptions;

    case Draft = 'draft';
    case Published = 'published';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('documentation.status.draft'),
            self::Published => __('documentation.status.published'),
        };
    }
}
