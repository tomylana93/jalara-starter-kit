<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * The one test that runs a real backup.
 *
 * Almost every decision in this feature lives in `config/backup.php`, where a
 * wrong path or a missing key breaks nothing that a mocked test would notice -
 * it breaks at 05:00 in production, silently. So this exercises the real
 * configuration end to end and only substitutes what the test environment makes
 * impossible: the suite runs on an in-memory SQLite database, which has no file
 * for the dumper to read.
 *
 * Note what is NOT overridden: the include paths, the destination disks, the
 * archive layout. Those are the values under test.
 */
beforeEach(function (): void {
    Storage::fake('backups');

    $this->databasePath = tempnam(sys_get_temp_dir(), 'backup-test-').'.sqlite';
    touch($this->databasePath);
    config()->set('database.connections.sqlite.database', $this->databasePath);

    /*
     * A real file under the configured media prefix, written to the real path
     * because the backup config names the directory rather than the disk.
     */
    $this->mediaPath = storage_path('app/public/backup-test-'.Str::uuid7()->toString().'.txt');
    file_put_contents($this->mediaPath, 'media-contents');
});

afterEach(function (): void {
    @unlink($this->databasePath);
    @unlink($this->mediaPath);
});

it('writes an archive containing the database dump and the configured media', function () {
    pendingCommand($this->artisan('backup:run'))->assertExitCode(0);

    $archives = Storage::disk('backups')->allFiles();

    expect($archives)->toHaveCount(1)
        ->and($archives[0])->toStartWith(config('backup.backup.name').'/')
        ->and($archives[0])->toEndWith('.zip');

    /*
     * Read the archive back rather than trusting the command's own report: the
     * question is what an operator would actually find inside it.
     */
    $localCopy = tempnam(sys_get_temp_dir(), 'backup-assert-').'.zip';
    file_put_contents($localCopy, Storage::disk('backups')->get($archives[0]));

    $zip = new ZipArchive;
    expect($zip->open($localCopy))->toBeTrue();

    $entries = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entries[] = (string) $zip->getNameIndex($index);
    }

    $zip->close();
    @unlink($localCopy);

    /* The dump is present. */
    expect(collect($entries)->contains(fn (string $entry): bool => str_starts_with($entry, 'db-dumps/')))
        ->toBeTrue();

    /* Media is stored at a project-relative path a restore can unpack over. */
    expect(collect($entries)->contains(basename(storage_path()).'/app/public/'.basename($this->mediaPath)))
        ->toBeTrue();

    /* The staging prefix stays out, and so does everything else in the project. */
    expect(collect($entries)->contains(fn (string $entry): bool => str_contains($entry, 'image-uploads')))
        ->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_contains($entry, 'vendor/')))
        ->toBeFalse()
        ->and(collect($entries)->contains(fn (string $entry): bool => str_ends_with($entry, '.env')))
        ->toBeFalse();
});
