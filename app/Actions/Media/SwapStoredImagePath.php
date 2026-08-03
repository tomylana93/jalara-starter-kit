<?php

namespace App\Actions\Media;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Points a settings value at an already-stored image without ever leaving an
 * orphan or a value referencing a missing file.
 *
 * This is the queue-side counterpart to `ReplaceStoredImage`: the bytes were
 * written by the worker rather than taken from the request, but the ordering
 * guarantee is identical. Persist the new path first and only then delete the
 * old file, so the previous image keeps serving until the replacement is real.
 *
 * "Only then" means after commit, not merely after the write. Saving a pointer
 * inside a transaction proves nothing until that transaction lands, and a
 * rollback would otherwise leave the restored value naming a file that had
 * already been deleted. Outside a transaction the deletion is immediate, so a
 * caller that does not need the guarantee does not pay for it.
 */
final class SwapStoredImagePath
{
    /**
     * @param  Closure(string): void  $persist
     */
    public function handle(string $disk, string $path, ?string $previousPath, Closure $persist): void
    {
        try {
            $persist($path);
        } catch (Throwable $throwable) {
            Storage::disk($disk)->delete($path);

            throw $throwable;
        }

        if (in_array($previousPath, [null, '', $path], true)) {
            return;
        }

        DB::afterCommit(function () use ($disk, $previousPath): void {
            Storage::disk($disk)->delete($previousPath);
        });
    }
}
