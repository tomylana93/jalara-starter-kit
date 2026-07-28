<?php

namespace App\Settings;

use App\Enums\DateFormat;
use App\Enums\Locale;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $applicationName;

    public ?string $description = null;

    public Locale $defaultLocale;

    public DateFormat $dateFormat;

    public static function group(): string
    {
        return 'general';
    }
}
