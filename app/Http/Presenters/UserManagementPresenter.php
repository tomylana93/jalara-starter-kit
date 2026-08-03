<?php

namespace App\Http\Presenters;

use App\Authorization\AuthorizationCatalog;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;

final class UserManagementPresenter
{
    /**
     * The values each table filter offers, labelled for the current locale.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function filterOptions(): array
    {
        return [
            'status' => UserStatus::options(),
            'role' => Role::options(),
        ];
    }

    /**
     * The roles user management is allowed to assign.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function roleOptions(AuthorizationCatalog $catalog): array
    {
        return array_map(
            fn (Role $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            $catalog->assignableRoles(),
        );
    }

    /**
     * Format the user data for the edit form.
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     status: string,
     *     role: string|null,
     * }
     */
    public static function editUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'role' => $user->primaryRole()?->value,
        ];
    }
}
