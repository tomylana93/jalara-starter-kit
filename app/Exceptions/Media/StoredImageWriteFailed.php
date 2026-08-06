<?php

namespace App\Exceptions\Media;

use RuntimeException;

/**
 * Raised when the public disk refuses to write an uploaded image.
 */
final class StoredImageWriteFailed extends RuntimeException
{
    public function __construct(string $directory)
    {
        parent::__construct(sprintf('Unable to store the uploaded image in [%s].', $directory));
    }
}
