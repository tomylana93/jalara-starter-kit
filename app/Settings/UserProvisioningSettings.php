<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Settings used when an administrator provisions a user account.
 *
 * The default password lives in its own group so no other runtime concern has
 * to resolve - and therefore decrypt - it.
 */
class UserProvisioningSettings extends Settings
{
    /**
     * The password assigned to administratively created users.
     *
     * Write-only: it is stored encrypted and never leaves the server. Use
     * hasDefaultPassword() for anything a client can observe.
     */
    public ?string $defaultPassword = null;

    public static function group(): string
    {
        return 'user_provisioning';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['defaultPassword'];
    }

    /**
     * Determine whether a default password has been configured.
     */
    public function hasDefaultPassword(): bool
    {
        return filled($this->defaultPassword);
    }

    /**
     * Represent the settings without ever exposing the default password.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['hasDefaultPassword' => $this->hasDefaultPassword()];
    }
}
