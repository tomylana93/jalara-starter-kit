<?php

namespace App\Actions\Account;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

final class CreateApiToken
{
    /**
     * Issue a personal access token for the user.
     *
     * The returned plain-text token is the only time it exists outside a hash;
     * the caller has one chance to show it before it is unrecoverable.
     */
    public function handle(User $user, string $name): NewAccessToken
    {
        return $user->createToken($name);
    }
}
