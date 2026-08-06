<?php

namespace App\Actions\Backups;

use App\Support\Backups\BackupArchives;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class UploadBackup
{
    public function __construct(private BackupArchives $archives) {}

    /**
     * Place an archive on the primary destination.
     *
     * The file is already known to be a backup archive: `UploadBackupRequest`
     * inspects its entries before this runs. What is left here is naming.
     *
     * The original basename is kept when it is free, so an archive downloaded
     * from this page and uploaded elsewhere keeps its identity. Note the one
     * thing naming cannot fix: retention orders archives by modification time,
     * and an uploaded file was modified now, so restoring history from an old
     * archive makes it the newest thing on the destination and it will outlive
     * genuinely older archives under the storage ceiling.
     */
    public function handle(UploadedFile $file): void
    {
        $directory = (string) config('backup.backup.name');
        $disk = Storage::disk($this->archives->primaryDiskName());

        $stem = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $stem = preg_replace('/[^A-Za-z0-9_-]/', '-', $stem);
        $stem = trim((string) $stem, '-');

        $filename = ($stem === '' ? 'backup' : $stem).'.zip';

        if ($disk->exists($directory.'/'.$filename)) {
            $filename = ($stem === '' ? 'backup' : $stem).'-'.now()->format('Y-m-d-H-i-s').'.zip';
        }

        $file->storeAs($directory, $filename, $this->archives->primaryDiskName());
    }
}
