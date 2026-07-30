<?php

namespace App\Actions\Account;

use App\Models\User;

final class UpdateProfile
{
    /**
     * Update the user's profile information.
     *
     * @param  array{name: string, email: string}  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
