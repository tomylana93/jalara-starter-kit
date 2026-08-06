<?php

namespace App\Data\Settings;

use App\Enums\DateFormat;
use App\Enums\Locale;

final readonly class UpdateGeneralSettingsData
{
    public function __construct(
        public string $applicationName,
        public ?string $description,
        public Locale $defaultLocale,
        public DateFormat $dateFormat,
    ) {}
}
