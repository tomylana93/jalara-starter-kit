<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateUser
{
    /**
     * Create a verified user, or return the existing user for an idempotent retry.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        $user = new User;
        $user->fill($attributes);
        $user->email_verified_at = now();

        if ($user->saveOrIgnore(uniqueBy: 'email')) {
            return $user;
        }

        return User::query()
            ->where('email', $attributes['email'])
            ->first() ?? throw new ModelNotFoundException;
    }
}
