<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    /**
     * Apply the Master Data changes to an existing user.
     *
     * An administratively changed email stays verified, and the single role is
     * replaced rather than appended.
     *
     * @param  array{name: string, email: string, status: string, role: string}  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $user->fill([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ]);

            $user->status = UserStatus::from($attributes['status']);

            /*
             * An expiry left over from an earlier suspension would silently
             * reactivate the account on its next request, so a status set here
             * is always open ended.
             */
            $user->suspended_until = null;

            $user->save();

            $user->syncRoles([$attributes['role']]);

            return $user;
        });
    }
}
