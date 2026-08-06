<?php

namespace App\Actions\Users;

use App\Data\Users\CreateManagedUserData;
use App\Data\Users\CreateUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateManagedUser
{
    public function __construct(private CreateUser $createUser) {}

    /**
     * Provision a user from Master Data and give it its single role.
     *
     * The status is never supplied here: the model default makes every new
     * account active, and the lifecycle is changed afterwards through an update.
     */
    public function handle(CreateManagedUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->createUser->handle(new CreateUserData(
                name: $data->name,
                email: $data->email,
            ));

            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
