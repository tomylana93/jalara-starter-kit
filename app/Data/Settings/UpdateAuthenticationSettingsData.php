<?php

namespace App\Data\Settings;

use App\Enums\PasswordPolicy;

final readonly class UpdateAuthenticationSettingsData
{
    public function __construct(
        public bool $requireEmailVerification,
        public PasswordPolicy $passwordPolicy,
        public int $sessionLifetimeMinutes,
    ) {}
}
