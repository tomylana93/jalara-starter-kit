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

    /**
     * Roles that user management may assign.
     *
     * Super Admin is granted by the authorization bootstrap alone, so CRUD can
     * never hand it out.
     *
     * @return list<Role>
     */
    public function assignableRoles(): array
    {
        return [Role::Admin, Role::User];
    }

    /** @return list<string> */
    public function assignableRoleValues(): array
    {
        return array_map(
            fn (Role $role): string => $role->value,
            $this->assignableRoles(),
        );
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
