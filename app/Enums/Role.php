<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    use HasOptions;

    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case User = 'user';

    /**
     * Get the human-readable, localized label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('user.role.super_admin'),
            self::Admin => __('user.role.admin'),
            self::User => __('user.role.user'),
        };
    }
}
