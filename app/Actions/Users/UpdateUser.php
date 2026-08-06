<?php

namespace App\Actions\Users;

use App\Data\Users\UpdateUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUser
{
    /**
     * Apply the Master Data changes to an existing user.
     *
     * An administratively changed email stays verified, and the single role is
     * replaced rather than appended.
     */
    public function handle(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->fill([
                'name' => $data->name,
                'email' => $data->email,
            ]);

            $user->status = $data->status;

            /*
             * An expiry left over from an earlier suspension would silently
             * reactivate the account on its next request, so a status set here
             * is always open ended.
             */
            $user->suspended_until = null;

            $user->save();

            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
