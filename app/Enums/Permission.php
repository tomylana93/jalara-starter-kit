<?php

namespace App\Enums;

use App\Concerns\HasOptions;
use App\Contracts\HasLabel;

enum Permission: string implements HasLabel
{
    use HasOptions;

    case ManageSettings = 'manage settings';

    /*
     * Deliberately separate from ManageSettings: this grant reaches a full copy
     * of the database through the archive download, which is a materially larger
     * trust than renaming the application or swapping a logo.
     */
    case ManageBackups = 'manage backups';
    case ViewUsers = 'view users';
    case CreateUsers = 'create users';
    case UpdateUsers = 'update users';

    public function label(): string
    {
        return match ($this) {
            self::ManageSettings => __('user.permission.manage_settings'),
            self::ManageBackups => __('user.permission.manage_backups'),
            self::ViewUsers => __('user.permission.view_users'),
            self::CreateUsers => __('user.permission.create_users'),
            self::UpdateUsers => __('user.permission.update_users'),
        };
    }
}
