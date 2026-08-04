<?php

use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

/**
 * Write a file and backdate it past the grace period.
 *
 * The sweep's own behaviour is covered in `tests/Feature/Media`; here files
 * only exist so the command has something to report on.
 */
function agedCommandFile(string $disk, string $path, string $contents = 'x'): string
{
    Storage::disk($disk)->put($path, $contents);
    touch(Storage::disk($disk)->path($path), now()->subDays(3)->getTimestamp());

    return $path;
}

it('reports orphans without deleting anything by default', function (): void {
    $orphan = agedCommandFile('public', 'avatars/old/orphan.png');

    pendingCommand(artisan('images:prune-orphans'))
        ->expectsOutputToContain(__('console.prune_orphan_images.mode.dry_run', ['hours' => 24]))
        ->assertSuccessful();

    Storage::disk('public')->assertExists($orphan);
});

it('forwards its options to the sweep', function (): void {
    Storage::disk('public')->put('avatars/recent/new.png', 'x');
    touch(
        Storage::disk('public')->path('avatars/recent/new.png'),
        now()->subHours(2)->getTimestamp(),
    );

    pendingCommand(artisan('images:prune-orphans', ['--delete' => true, '--older-than' => 1]))
        ->expectsOutputToContain(__('console.prune_orphan_images.mode.delete', ['hours' => 1]))
        ->assertSuccessful();

    Storage::disk('public')->assertMissing('avatars/recent/new.png');
});

it('reports the counts it acted on per disk', function (): void {
    agedCommandFile('public', 'avatars/old/orphan.png');

    pendingCommand(artisan('images:prune-orphans', ['--delete' => true]))
        ->expectsOutputToContain(__('console.prune_orphan_images.disk', [
            'disk' => 'public',
            'candidates' => 1,
            'deleted' => 1,
            'skipped' => 0,
        ]))
        ->expectsOutputToContain(__('console.prune_orphan_images.disk', [
            'disk' => 'local',
            'candidates' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ]))
        ->assertSuccessful();
});
