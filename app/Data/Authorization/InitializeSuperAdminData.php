<?php

namespace App\Data\Authorization;

use App\Enums\UserStatus;

/**
 * The configured identity of the system Super Admin.
 *
 * Built by the console command from configuration, never from a request, which
 * is why nothing here knows about HTTP.
 */
final readonly class InitializeSuperAdminData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public UserStatus $status,
        public bool $emailVerified,
        public ?string $password,
    ) {}
}
