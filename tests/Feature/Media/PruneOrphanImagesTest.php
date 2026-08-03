<?php

use App\Actions\Media\PruneOrphanImages;
use App\Actions\Media\PruneOrphanImagesResult;
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
function agedImage(string $disk, string $path, string $contents = 'x'): string
{
    Storage::disk($disk)->put($path, $contents);
    touch(Storage::disk($disk)->path($path), now()->subDays(3)->getTimestamp());

    return $path;
}

function sweep(int $hours = 24, bool $delete = true): PruneOrphanImagesResult
{
    return app(PruneOrphanImages::class)->handle($hours, $delete);
}

it('reports orphans without deleting anything by default', function (): void {
    $orphan = agedImage('public', 'avatars/old/orphan.png');

    $result = app(PruneOrphanImages::class)->handle();

    Storage::disk('public')->assertExists($orphan);

    expect($result->dryRun)->toBeTrue()
        ->and($result->disks['public']['candidates'])->toBe(1)
        ->and($result->disks['public']['deleted'])->toBe(0);
});

it('deletes unreferenced managed files older than the grace period', function (): void {
    $publicOrphan = agedImage('public', 'avatars/old/orphan.png');
    $brandingOrphan = agedImage('public', 'branding/logos/orphan.png');
    $chatOrphan = agedImage('local', 'chat/old/orphan.png');
    $stagedOrphan = agedImage('local', ImageUpload::SOURCE_DIRECTORY.'/orphan.png');

    $result = sweep();

    Storage::disk('public')->assertMissing($publicOrphan);
    Storage::disk('public')->assertMissing($brandingOrphan);
    Storage::disk('local')->assertMissing($chatOrphan);
    Storage::disk('local')->assertMissing($stagedOrphan);

    expect($result->dryRun)->toBeFalse()
        ->and($result->disks['public']['deleted'])->toBe(2)
        ->and($result->disks['local']['deleted'])->toBe(2);
});

it('reports counts for every managed disk even when nothing matches', function (): void {
    $result = sweep();

    expect($result->disks)->toHaveKeys(['public', 'local'])
        ->and($result->disks['public'])->toBe(['candidates' => 0, 'deleted' => 0, 'skipped' => 0])
        ->and($result->disks['local'])->toBe(['candidates' => 0, 'deleted' => 0, 'skipped' => 0]);
});

it('keeps a file an avatar still points at', function (): void {
    $path = agedImage('public', 'avatars/kept/avatar.png');

    $user = User::factory()->create();
    $user->avatar_path = $path;
    $user->save();

    $result = sweep();

    Storage::disk('public')->assertExists($path);

    expect($result->disks['public']['skipped'])->toBe(1);
});

it('keeps a file a branding setting still points at', function (): void {
    $path = agedImage('public', 'branding/logos/kept.png');

    $settings = app(BrandingSettings::class);
    $settings->logoPath = $path;
    $settings->save();

    sweep();

    Storage::disk('public')->assertExists($path);
});

it('keeps a file a chat message still points at', function (): void {
    $path = agedImage('local', 'chat/kept/image.png');

    Message::factory()->create(['image_path' => $path, 'image_mime_type' => 'image/png']);

    sweep();

    Storage::disk('local')->assertExists($path);
});

it('keeps the staged source of an upload that has not finished', function (): void {
    $path = agedImage('local', ImageUpload::SOURCE_DIRECTORY.'/active.png');

    ImageUpload::factory()->create(['source_path' => $path]);

    sweep();

    Storage::disk('local')->assertExists($path);
});

it('keeps a result an upload record still owns', function (): void {
    $path = agedImage('public', 'avatars/kept/result.png');

    ImageUpload::factory()->create([
        'target' => ImageUploadTarget::Avatar,
        'result_path' => $path,
    ]);

    sweep();

    Storage::disk('public')->assertExists($path);
});

it('keeps a file that is younger than the grace period', function (): void {
    Storage::disk('public')->put('avatars/fresh/new.png', 'x');

    sweep();

    Storage::disk('public')->assertExists('avatars/fresh/new.png');
});

it('honours a custom grace period', function (): void {
    Storage::disk('public')->put('avatars/recent/new.png', 'x');
    touch(
        Storage::disk('public')->path('avatars/recent/new.png'),
        now()->subHours(2)->getTimestamp(),
    );

    /* Older than one hour, so in scope. */
    sweep(hours: 1);

    Storage::disk('public')->assertMissing('avatars/recent/new.png');
});

it('treats a negative grace period as no grace period', function (): void {
    $orphan = agedImage('public', 'avatars/old/orphan.png');

    $result = sweep(hours: -5, delete: false);

    Storage::disk('public')->assertExists($orphan);

    expect($result->hours)->toBe(0);
});

it('never touches a symlink', function (): void {
    $outside = Storage::disk('local')->path('outside-target.png');
    file_put_contents($outside, 'x');

    $linkPath = Storage::disk('public')->path('avatars/linked.png');
    @mkdir(dirname($linkPath), 0o777, true);
    symlink($outside, $linkPath);
    touch($linkPath, now()->subDays(3)->getTimestamp());

    sweep();

    expect(is_link($linkPath))->toBeTrue()
        ->and(file_exists($outside))->toBeTrue();
});

it('never deletes files outside the managed prefixes', function (): void {
    $unmanaged = agedImage('public', 'documents/report.png');
    $unmanagedRoot = agedImage('local', 'exports/data.png');

    sweep();

    Storage::disk('public')->assertExists($unmanaged);
    Storage::disk('local')->assertExists($unmanagedRoot);
});

it('leaves directories themselves alone', function (): void {
    Storage::disk('public')->makeDirectory('avatars/empty-directory');

    sweep();

    expect(Storage::disk('public')->path('avatars/empty-directory'))->toBeDirectory();
});
