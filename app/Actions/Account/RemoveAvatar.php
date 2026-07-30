<?php

namespace App\Actions\Account;

use App\Actions\Media\DeleteStoredImage;
use App\Models\User;

final readonly class RemoveAvatar
{
    public function __construct(private DeleteStoredImage $deleteStoredImage) {}

    /**
     * Clear the user's stored avatar.
     */
    public function handle(User $user): User
    {
        $this->deleteStoredImage->handle(
            $user->avatar_path,
            function () use ($user): void {
                $user->avatar_path = null;
                $user->save();
            },
        );

        return $user;
    }
}
