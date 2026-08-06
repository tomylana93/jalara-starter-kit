<?php

namespace App\Data\Authorization;

/**
 * The configured identity of the system Super Admin.
 *
 * Built by the console command from configuration, never from a request, which
 * is why nothing here knows about HTTP.
 *
 * There is deliberately no status: the system account is always active, and
 * `InitializeSuperAdmin` restores that on every run.
 */
final readonly class InitializeSuperAdminData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public bool $emailVerified,
        public ?string $password,
    ) {}
}
