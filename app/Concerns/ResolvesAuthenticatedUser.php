<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesAuthenticatedUser
{
    /**
     * Resolve the authenticated application user.
     *
     * The routes already run behind `auth`; this narrows the authenticatable
     * contract to the application model so every query below starts from a
     * concrete user rather than a nullable guard result.
     */
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
