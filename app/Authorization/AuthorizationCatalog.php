<?php

namespace App\Authorization;

use App\Enums\Permission;
use App\Enums\Role;

final class AuthorizationCatalog
{
    /** @return list<Role> */
    public function roles(): array
    {
        return [Role::SuperAdmin, Role::Admin, Role::User];
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return [
            Permission::ManageSettings,
            Permission::ViewUsers,
            Permission::CreateUsers,
            Permission::UpdateUsers,
        ];
    }

    /** @return list<Permission> */
    public function permissionsFor(Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => $this->permissions(),
            Role::Admin => [
                Permission::ViewUsers,
                Permission::CreateUsers,
                Permission::UpdateUsers,
            ],
            Role::User => [],
        };
    }
}
