<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
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

/**
 * Post an archive to the upload endpoint as a backup manager would.
 *
 * @return TestResponse<RedirectResponse>
 */
function uploadArchive(string $path, string $name = 'archive.zip'): TestResponse
{
    return actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.upload'), ['archive' => uploadedArchive($path, $name)]);
}

it('accepts an archive this application could have produced', function () {
    $path = backupArchiveFile([
        'db-dumps/database' => "create table widgets (id integer);\n",
        'storage/app/public/logo.png' => 'image-bytes',
    ]);

    uploadArchive($path, 'my-backup.zip')
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('settings.backups.index');

    Storage::disk('backups')
        ->assertExists(config('backup.backup.name').'/my-backup.zip');

    @unlink($path);
});

/*
 * The escalation this validation exists to stop.
 *
 * An uploaded archive is restorable, and a restore copies the archive's files
 * over the project root. An entry under `storage/framework/views` would be a
 * compiled Blade template - arbitrary code, executed on the next render, from
 * nothing but the `manage backups` permission. It must never reach the disk.
 */
it('rejects an archive carrying a path no backup contains', function () {
    $path = backupArchiveFile([
        'db-dumps/database' => 'select 1;',
        'storage/framework/views/pwn.php' => '<?php echo "pwned";',
    ]);

    uploadArchive($path)->assertSessionHasErrors('archive');

    expect(Storage::disk('backups')->files(config('backup.backup.name')))->toBeEmpty();

    @unlink($path);
});

it('rejects a zip that holds nothing a backup would', function () {
    $path = backupArchiveFile(['notes.txt' => 'not a backup']);

    uploadArchive($path)->assertSessionHasErrors('archive');

    expect(Storage::disk('backups')->files(config('backup.backup.name')))->toBeEmpty();

    @unlink($path);
});

/*
 * A `.zip` name and a declared MIME type are both the client's word. Only the
 * bytes decide.
 */
it('rejects a file that is not a zip at all', function () {
    $path = tempnam(sys_get_temp_dir(), 'not-a-zip-').'.zip';
    file_put_contents($path, 'plain text pretending to be an archive');

    uploadArchive($path)->assertSessionHasErrors('archive');

    expect(Storage::disk('backups')->files(config('backup.backup.name')))->toBeEmpty();

    @unlink($path);
});

/*
 * An operator uploading an archive they downloaded from another installation
 * must not overwrite a local archive that happens to share its timestamped name.
 */
it('keeps an uploaded archive from overwriting one already on the destination', function () {
    $existing = fakeArchive();
    $path = backupArchiveFile(['db-dumps/database' => 'select 1;']);

    uploadArchive($path, $existing)->assertSessionHasNoErrors();

    expect(Storage::disk('backups')->files(config('backup.backup.name')))->toHaveCount(2)
        ->and(Storage::disk('backups')->get(config('backup.backup.name').'/'.$existing))
        ->toBe('archive-contents');

    @unlink($path);
});

/*
 * A trailing `..` segment climbs out of the prefix it appears to sit in just as
 * `../` does, so the inspector rejects any segment rather than that one spelling.
 */
it('rejects an archive whose entry climbs out of its own prefix', function () {
    $path = backupArchiveFile([
        'db-dumps/database' => 'select 1;',
        'storage/app/public/..' => 'climbing',
    ]);

    uploadArchive($path)->assertSessionHasErrors('archive');

    expect(Storage::disk('backups')->files(config('backup.backup.name')))->toBeEmpty();

    @unlink($path);
});
