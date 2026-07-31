<?php

namespace App\Policies;

use App\Enums\DocumentationStatus;
use App\Enums\Role;
use App\Models\Documentation;
use App\Models\User;

class DocumentationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Documentation $documentation): bool
    {
        return $documentation->status === DocumentationStatus::Published || $this->create($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin->value);
    }

    public function update(User $user, Documentation $documentation): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Documentation $documentation): bool
    {
        return $this->create($user);
    }
}
