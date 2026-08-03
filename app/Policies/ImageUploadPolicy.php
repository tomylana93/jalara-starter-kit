<?php

namespace App\Policies;

use App\Models\ImageUpload;
use App\Models\User;

/**
 * An upload is private to the person who started it.
 *
 * Even a Super Admin has no reason to read someone else's in-flight image, so
 * ownership is the only rule here.
 */
class ImageUploadPolicy
{
    public function view(User $actor, ImageUpload $upload): bool
    {
        return $upload->user_id === $actor->getKey();
    }

    public function cancel(User $actor, ImageUpload $upload): bool
    {
        return $this->view($actor, $upload);
    }
}
