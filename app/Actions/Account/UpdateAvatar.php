<?php

namespace App\Actions\Account;

use App\Actions\Media\ReplaceStoredImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final readonly class UpdateAvatar
{
    public function __construct(private ReplaceStoredImage $replaceStoredImage) {}

    /**
     * Store a new avatar for the user.
     */
    public function handle(User $user, UploadedFile $file): User
    {
        $this->replaceStoredImage->handle(
            $file,
            sprintf('avatars/%s', $user->getKey()),
            $user->avatar_path,
            function (string $path) use ($user): void {
                $user->avatar_path = $path;
                $user->save();
            },
        );

        return $user;
    }
}
