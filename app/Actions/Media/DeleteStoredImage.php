<?php

namespace App\Actions\Media;

use Closure;
use Illuminate\Support\Facades\Storage;

/**
 * Clears a stored image reference and then removes the file.
 *
 * Persistence runs first so a failure leaves the file reachable rather than
 * leaving the stored path pointing at nothing.
 */
final class DeleteStoredImage
{
    /**
     * @param  Closure(): void  $persist
     */
    public function handle(?string $path, Closure $persist): void
    {
        $persist();

        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
