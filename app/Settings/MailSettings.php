<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MailSettings extends Settings
{
    public string $fromName;

    public string $fromAddress;

    public static function group(): string
    {
        return 'mail';
    }
}
