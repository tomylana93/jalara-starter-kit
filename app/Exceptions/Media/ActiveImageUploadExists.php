<?php

namespace App\Exceptions\Media;

use App\Models\ImageUpload;
use RuntimeException;

/**
 * Raised when a target that allows one upload at a time already has one.
 *
 * The conflict is detected by the unique `lock_key` index rather than by a
 * preceding read, so two simultaneous requests cannot both win.
 */
final class ActiveImageUploadExists extends RuntimeException
{
    public function __construct(public readonly ImageUpload $existing)
    {
        parent::__construct('An image upload for this target is already in progress.');
    }
}
