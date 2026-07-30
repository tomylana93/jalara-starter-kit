<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the actor may browse the Master Data user listing.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ViewUsers->value);
    }

    /**
     * Determine whether the actor may provision a new user.
     */
    public function create(User $actor): bool
    {
        return $actor->can(Permission::CreateUsers->value);
    }

    /**
     * Determine whether the actor may edit the target user.
     *
     * The system account and every Super Admin stay outside Master Data: their
     * lifecycle belongs to the authorization bootstrap, not to CRUD.
     */
    public function update(User $actor, User $target): bool
    {
        return $actor->can(Permission::UpdateUsers->value)
            && ! $target->is_system
            && ! $target->hasRole(Role::SuperAdmin->value);
    }

    public function disableAccount(User $actor, User $target): bool
    {
        return $actor->is($target)
            && ! $target->is_system
            && ! $target->hasRole(Role::SuperAdmin->value);
    }
}
