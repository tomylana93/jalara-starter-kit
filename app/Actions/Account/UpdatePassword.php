<?php

namespace App\Actions\Account;

use App\Models\User;

final class UpdatePassword
{
    /**
     * Update the user's password.
     */
    public function handle(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
