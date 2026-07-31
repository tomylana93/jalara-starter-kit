<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\DocumentationCategory;
use App\Models\User;

class DocumentationCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DocumentationCategory $documentationCategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin->value);
    }

    public function update(User $user, DocumentationCategory $documentationCategory): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, DocumentationCategory $documentationCategory): bool
    {
        return $this->create($user);
    }
}
