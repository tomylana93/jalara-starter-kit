<?php

namespace App\Actions\Media;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Swaps a stored image for a newly uploaded one without ever leaving an
 * orphaned file or a settings value pointing at a missing file.
 *
 * The order is deliberate: write the new file, persist the new path, and only
 * then delete the old file. If persistence fails the new file is removed again,
 * so a failed request leaves storage exactly as it found it.
 */
final class ReplaceStoredImage
{
    /**
     * Store the upload and hand the resulting path to the persistence callback.
     *
     * @param  Closure(string): void  $persist
     */
    public function handle(UploadedFile $file, string $directory, ?string $previousPath, Closure $persist): string
    {
        $disk = Storage::disk('public');

        /*
         * A generated name, never the user-supplied one, keeps traversal and
         * collision out of the storage path entirely.
         */
        $path = $disk->putFile($directory, $file);

        throw_if($path === false, StoredImageWriteFailed::class, $directory);

        try {
            $persist($path);
        } catch (Throwable $throwable) {
            $disk->delete($path);

            throw $throwable;
        }

        if (! in_array($previousPath, [null, '', $path], true)) {
            $disk->delete($previousPath);
        }

        return $path;
    }
}
