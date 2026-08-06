<?php

namespace App\Data\Account;

final readonly class UpdateProfileData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
