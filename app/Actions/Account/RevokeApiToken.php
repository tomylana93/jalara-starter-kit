<?php

namespace App\Actions\Account;

use App\Models\User;

final class RevokeApiToken
{
    /**
     * Delete one of the user's personal access tokens.
     *
     * Scoped through the relation rather than by primary key alone, so a token
     * belonging to somebody else is a no-op instead of a deletion.
     */
    public function handle(User $user, string $tokenId): bool
    {
        return (bool) $user->tokens()->whereKey($tokenId)->delete();
    }
}
