<?php

use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * Put an archive on the faked destination and return its basename.
 */
function fakeArchive(string $filename = '2026-01-01-00-00-00.zip'): string
{
    Storage::disk('backups')->put(
        config('backup.backup.name').'/'.$filename,
        'archive-contents',
    );

    return $filename;
}

beforeEach(function (): void {
    Storage::fake('backups');
});

it('lists the archives on the destination', function () {
    $filename = fakeArchive();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.backups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Backups')
            ->has('archives', 1)
            ->where('archives.0.filename', $filename)
            ->where('archives.0.disk', 'backups')
            ->where('activeRun', null));
});

it('downloads an archive that exists', function () {
    $filename = fakeArchive();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.backups.download', ['filename' => $filename]))
        ->assertOk()
        ->assertDownload($filename);
});

it('deletes an archive that exists', function () {
    $filename = fakeArchive();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('settings.backups.destroy', ['filename' => $filename]))
        ->assertRedirectToRoute('settings.backups.index');

    Storage::disk('backups')
        ->assertMissing(config('backup.backup.name').'/'.$filename);
});

/*
 * The filename is matched against the real listing rather than joined onto a
 * root, so a name pointing anywhere else resolves to nothing at all. These are
 * the cases that would otherwise turn an admin session into arbitrary file read
 * and arbitrary file delete.
 */
it('refuses to download a filename that is not a known archive', function (string $filename) {
    fakeArchive();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/settings/backups/'.$filename.'/download')
        ->assertNotFound();
})->with([
    'traversal' => '..%2F..%2F.env',
    'encoded traversal' => '%2E%2E%2F.env',
    'unknown name' => 'not-a-backup.zip',
    'absolute path' => '%2Fetc%2Fpasswd',
]);

it('refuses to delete a filename that is not a known archive', function () {
    $filename = fakeArchive();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete('/settings/backups/not-a-backup.zip')
        ->assertNotFound();

    /* The real archive is untouched by the rejected request. */
    Storage::disk('backups')
        ->assertExists(config('backup.backup.name').'/'.$filename);
});
