<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum UserStatus: string implements HasLabel
{
    use HasOptions;

    case Active = 'active';
    case Disabled = 'disabled';
    case Suspended = 'suspended';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('user.status.active'),
            self::Disabled => __('user.status.disabled'),
            self::Suspended => __('user.status.suspended'),
        };
    }

    /**
     * Get the message associated with the status.
     */
    public function message(): string
    {
        return match ($this) {
            self::Active => __('auth.login.message.active'),
            self::Disabled => __('auth.login.message.disabled'),
            self::Suspended => __('auth.login.message.suspended'),
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Active => 'default',
            self::Disabled => 'destructive',
            self::Suspended => 'secondary',
        };
    }
}
