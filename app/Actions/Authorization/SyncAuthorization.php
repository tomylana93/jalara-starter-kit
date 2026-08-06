<?php

namespace App\Actions\Authorization;

use App\Authorization\AuthorizationCatalog;
use App\Data\Authorization\AuthorizationSyncResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class SyncAuthorization
{
    public function __construct(
        private AuthorizationCatalog $catalog,
        private PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(bool $dryRun = false): AuthorizationSyncResult
    {
        $result = $this->changes($dryRun);

        if ($dryRun) {
            return $result;
        }

        DB::transaction(function (): void {
            $permissions = collect($this->catalog->permissions())
                ->mapWithKeys(fn ($permission): array => [
                    $permission->value => Permission::findOrCreate($permission->value, 'web'),
                ]);

            foreach ($this->catalog->roles() as $catalogRole) {
                Role::findOrCreate($catalogRole->value, 'web')->syncPermissions(
                    collect($this->catalog->permissionsFor($catalogRole))
                        ->map(fn ($permission) => $permissions->get($permission->value))
                        ->all(),
                );
            }

            Role::query()->where('guard_name', 'web')
                ->whereNotIn('name', collect($this->catalog->roles())->pluck('value'))
                ->delete();
            Permission::query()->where('guard_name', 'web')
                ->whereNotIn('name', collect($this->catalog->permissions())->pluck('value'))
                ->delete();
        });

        $this->permissionRegistrar->forgetCachedPermissions();

        return $result;
    }

    private function changes(bool $dryRun): AuthorizationSyncResult
    {
        $catalogRoleNames = collect($this->catalog->roles())->map(fn ($role): string => $role->value);
        $catalogPermissionNames = collect($this->catalog->permissions())->map(fn ($permission): string => $permission->value);
        $existingRoles = Role::query()->where('guard_name', 'web')->with('permissions')->get();
        $existingRoleNames = $existingRoles->toBase()->map(fn (Role $role): string => (string) $role->name);
        $existingPermissions = Permission::query()->where('guard_name', 'web')->get()
            ->toBase()
            ->map(fn (Permission $permission): string => (string) $permission->name);
        $permissionsToAttachByRole = [];
        $permissionsToDetachByRole = [];

        foreach ($this->catalog->roles() as $catalogRole) {
            $expected = collect($this->catalog->permissionsFor($catalogRole))
                ->map(fn ($permission): string => $permission->value);
            $current = $existingRoles->firstWhere('name', $catalogRole->value)?->permissions
                ->toBase()
                ->map(fn (Permission $permission): string => (string) $permission->name) ?? collect();
            $permissionsToAttachByRole[$catalogRole->value] = $this->sortedList($expected->diff($current));
            $permissionsToDetachByRole[$catalogRole->value] = $this->sortedList($current->diff($expected));
        }

        return new AuthorizationSyncResult(
            dryRun: $dryRun,
            rolesToCreate: $this->sortedList($catalogRoleNames->diff($existingRoleNames)),
            permissionsToCreate: $this->sortedList($catalogPermissionNames->diff($existingPermissions)),
            rolesToDelete: $this->sortedList($existingRoleNames->diff($catalogRoleNames)),
            permissionsToDelete: $this->sortedList($existingPermissions->diff($catalogPermissionNames)),
            permissionsToAttachByRole: $permissionsToAttachByRole,
            permissionsToDetachByRole: $permissionsToDetachByRole,
        );
    }

    /**
     * @param  Collection<int, string>  $values
     * @return list<string>
     */
    private function sortedList(Collection $values): array
    {
        return array_values($values->sort()->all());
    }
}
