<?php

namespace App\Actions\Account;

use App\Models\User;

final class DeleteAccount
{
    /**
     * Delete the user's account.
     */
    public function handle(User $user): void
    {
        $user->delete();
    }
}
