<?php

namespace App\Settings;

use App\Enums\PasswordPolicy;
use Spatie\LaravelSettings\Settings;

class AuthenticationSettings extends Settings
{
    public bool $requireEmailVerification;

    public PasswordPolicy $passwordPolicy;

    public int $sessionLifetimeMinutes;

    public static function group(): string
    {
        return 'authentication';
    }
}
