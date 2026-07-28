<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function disableAccount(User $actor, User $target): bool
    {
        return $actor->is($target)
            && ! $target->is_system
            && ! $target->hasRole(Role::SuperAdmin->value);
    }
}
