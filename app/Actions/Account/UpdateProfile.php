<?php

namespace App\Actions\Account;

use App\Data\Account\UpdateProfileData;
use App\Models\User;

final class UpdateProfile
{
    /**
     * Update the user's profile information.
     */
    public function handle(User $user, UpdateProfileData $data): void
    {
        $user->fill([
            'name' => $data->name,
            'email' => $data->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
