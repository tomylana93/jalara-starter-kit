<?php

namespace App\Data\Users;

use App\Enums\Role;
use App\Enums\UserStatus;

final readonly class UpdateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public UserStatus $status,
        public Role $role,
    ) {}
}
