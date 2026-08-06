<?php

namespace App\Data\Users;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
