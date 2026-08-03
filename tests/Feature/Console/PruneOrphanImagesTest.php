<?php

use App\Enums\ImageUploadTarget;
use App\Models\Chat\Message;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\BrandingSettings;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

/**
 * Write a file and backdate it past the grace period.
 */
function agedFile(string $disk, string $path, string $contents = 'x'): string
{
    Storage::disk($disk)->put($path, $contents);
    touch(Storage::disk($disk)->path($path), now()->subDays(3)->getTimestamp());

    return $path;
}

it('reports orphans without deleting anything by default', function (): void {
    $orphan = agedFile('public', 'avatars/old/orphan.png');

    pendingCommand($this->artisan('images:prune-orphans'))->assertSuccessful();

    Storage::disk('public')->assertExists($orphan);
});

it('deletes unreferenced managed files older than the grace period', function (): void {
    $publicOrphan = agedFile('public', 'avatars/old/orphan.png');
    $brandingOrphan = agedFile('public', 'branding/logos/orphan.png');
    $chatOrphan = agedFile('local', 'chat/old/orphan.png');
    $stagedOrphan = agedFile('local', ImageUpload::SOURCE_DIRECTORY.'/orphan.png');

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('public')->assertMissing($publicOrphan);
    Storage::disk('public')->assertMissing($brandingOrphan);
    Storage::disk('local')->assertMissing($chatOrphan);
    Storage::disk('local')->assertMissing($stagedOrphan);
});

it('keeps a file an avatar still points at', function (): void {
    $path = agedFile('public', 'avatars/kept/avatar.png');

    $user = User::factory()->create();
    $user->avatar_path = $path;
    $user->save();

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('public')->assertExists($path);
});

it('keeps a file a branding setting still points at', function (): void {
    $path = agedFile('public', 'branding/logos/kept.png');

    $settings = app(BrandingSettings::class);
    $settings->logoPath = $path;
    $settings->save();

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('public')->assertExists($path);
});

it('keeps a file a chat message still points at', function (): void {
    $path = agedFile('local', 'chat/kept/image.png');

    Message::factory()->create(['image_path' => $path, 'image_mime_type' => 'image/png']);

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('local')->assertExists($path);
});

it('keeps the staged source of an upload that has not finished', function (): void {
    $path = agedFile('local', ImageUpload::SOURCE_DIRECTORY.'/active.png');

    ImageUpload::factory()->create(['source_path' => $path]);

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('local')->assertExists($path);
});

it('keeps a result an upload record still owns', function (): void {
    $path = agedFile('public', 'avatars/kept/result.png');

    ImageUpload::factory()->create([
        'target' => ImageUploadTarget::Avatar,
        'result_path' => $path,
    ]);

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('public')->assertExists($path);
});

it('keeps a file that is younger than the grace period', function (): void {
    Storage::disk('public')->put('avatars/fresh/new.png', 'x');

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('public')->assertExists('avatars/fresh/new.png');
});

it('honours a custom grace period', function (): void {
    Storage::disk('public')->put('avatars/recent/new.png', 'x');
    touch(
        Storage::disk('public')->path('avatars/recent/new.png'),
        now()->subHours(2)->getTimestamp(),
    );

    /* Older than one hour, so in scope. */
    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true, '--older-than' => 1]))
        ->assertSuccessful();

    Storage::disk('public')->assertMissing('avatars/recent/new.png');
});

it('never touches a symlink', function (): void {
    $outside = Storage::disk('local')->path('outside-target.png');
    file_put_contents($outside, 'x');

    $linkPath = Storage::disk('public')->path('avatars/linked.png');
    @mkdir(dirname($linkPath), 0o777, true);
    symlink($outside, $linkPath);
    touch($linkPath, now()->subDays(3)->getTimestamp());

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    expect(is_link($linkPath))->toBeTrue()
        ->and(file_exists($outside))->toBeTrue();
});

it('never deletes files outside the managed prefixes', function (): void {
    $unmanaged = agedFile('public', 'documents/report.png');
    $unmanagedRoot = agedFile('local', 'exports/data.png');

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    Storage::disk('public')->assertExists($unmanaged);
    Storage::disk('local')->assertExists($unmanagedRoot);
});

it('leaves directories themselves alone', function (): void {
    Storage::disk('public')->makeDirectory('avatars/empty-directory');

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))->assertSuccessful();

    expect(Storage::disk('public')->path('avatars/empty-directory'))->toBeDirectory();
});

it('reports the counts it acted on per disk', function (): void {
    agedFile('public', 'avatars/old/orphan.png');

    pendingCommand($this->artisan('images:prune-orphans', ['--delete' => true]))
        ->expectsOutputToContain('public')
        ->expectsOutputToContain('local')
        ->assertSuccessful();
});
