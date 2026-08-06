<?php

namespace App\Rules\Backups;

use App\Support\Backups\BackupArchiveContents;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts only an archive this application could have produced.
 *
 * The extension and the reported MIME type say nothing about what a ZIP
 * contains, and an uploaded archive is restorable - it executes its own SQL and
 * writes its own files back. So the contents are inspected here, at the door,
 * rather than at restore time when the operator has already been shown the file
 * sitting in the archive list as if it were a backup.
 */
final class IsBackupArchive implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('backup.validation.archive')->translate();

            return;
        }

        $path = $value->getRealPath();

        if ($path === false || ! BackupArchiveContents::tryRead($path) instanceof BackupArchiveContents) {
            $fail('backup.validation.archive')->translate();
        }
    }
}
