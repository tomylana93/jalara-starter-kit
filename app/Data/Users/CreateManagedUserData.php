<?php

namespace App\Data\Users;

use App\Enums\Role;

final readonly class CreateManagedUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public Role $role,
    ) {}
}
