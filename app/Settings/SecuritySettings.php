<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    public int $maxFailedLoginAttempts;

    public int $suspensionDurationMinutes;

    public bool $maintenanceEnabled;

    public static function group(): string
    {
        return 'security';
    }
}
