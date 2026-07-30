<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateManagedUser
{
    public function __construct(private readonly CreateUser $createUser) {}

    /**
     * Provision a user from Master Data and give it its single role.
     *
     * The status is never supplied here: the model default makes every new
     * account active, and the lifecycle is changed afterwards through an update.
     *
     * @param  array{name: string, email: string, role: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = $this->createUser->handle([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ]);

            $user->syncRoles([$attributes['role']]);

            return $user;
        });
    }
}
