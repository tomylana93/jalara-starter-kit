<?php

namespace App\Actions\Users;

use App\Data\Users\CreateUserData;
use App\Exceptions\DefaultUserPasswordNotConfigured;
use App\Models\User;
use App\Settings\SettingsResolver;
use App\Settings\UserProvisioningSettings;

final class CreateUser
{
    /**
     * Create a verified user, or return the existing user for an idempotent retry.
     *
     * The password is never supplied by the caller: every administratively
     * created user starts from the configured default password and must change
     * it on first sign in.
     *
     * @throws DefaultUserPasswordNotConfigured When a new user is created without a configured default password.
     */
    public function handle(CreateUserData $data): User
    {
        /*
         * A retry stays idempotent even when the default password was removed
         * after the user was first created.
         */
        $existingUser = User::query()->where('email', $data->email)->first();

        if ($existingUser instanceof User) {
            return $existingUser;
        }

        $user = new User;
        $user->fill([
            'name' => $data->name,
            'email' => $data->email,
        ]);

        /* The hashed cast turns the shared plaintext into a per-user hash. */
        $user->password = $this->defaultPassword();
        $user->must_change_password = true;
        $user->email_verified_at = now();

        if ($user->saveOrIgnore(uniqueBy: 'email')) {
            return $user;
        }

        /* Another request won the race on the unique email. */
        return User::query()
            ->where('email', $data->email)
            ->firstOrFail();
    }

    /**
     * Read the configured default password for a new user.
     *
     * @throws DefaultUserPasswordNotConfigured
     */
    private function defaultPassword(): string
    {
        $defaultPassword = SettingsResolver::tryResolve(UserProvisioningSettings::class)?->defaultPassword;

        throw_if($defaultPassword === null || $defaultPassword === '', DefaultUserPasswordNotConfigured::class);

        return $defaultPassword;
    }
}
