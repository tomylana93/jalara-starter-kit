<?php

use App\Actions\Media\CancelImageUpload;
use App\Actions\Media\ClaimImageUpload;
use App\Actions\Media\CompleteImageUpload;
use App\Actions\Media\FailImageUpload;
use App\Actions\Media\StageImageUpload;
use App\Actions\Media\SwapStoredImagePath;
use App\Enums\BrandingAsset;
use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Jobs\Media\ProcessAvatarImageUpload;
use App\Jobs\Media\ProcessBrandingImageUpload;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\BrandingSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

/**
 * Stage a real image the way an accepted request would.
 */
function stage(
    User $user,
    UploadedFile $file,
    ImageUploadTarget $target = ImageUploadTarget::Avatar,
    ?string $targetKey = null,
): ImageUpload {
    return app(StageImageUpload::class)->handle($user, $file, $target, $targetKey);
}

function runAvatarJob(ImageUpload $upload): void
{
    app()->call([new ProcessAvatarImageUpload($upload), 'handle']);
}

/**
 * @return array{0: int, 1: int, mime: string}
 */
function storedImage(string $disk, string $path): array
{
    $size = getimagesizefromstring((string) Storage::disk($disk)->get($path));

    throw_if($size === false, RuntimeException::class, "The stored file at [{$path}] is not a readable image.");

    return [$size[0], $size[1], 'mime' => $size['mime']];
}

it('keeps a PNG as a PNG', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    runAvatarJob($upload);
    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Ready)
        ->and($upload->result_mime_type)->toBe('image/png')
        ->and($upload->result_path)->toEndWith('.png')
        ->and(storedImage('public', $upload->result_path)['mime'])->toBe('image/png');
});

it('converts a JPEG to WebP', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.jpg', 400, 400));

    runAvatarJob($upload);
    $upload->refresh();

    expect($upload->result_mime_type)->toBe('image/webp')
        ->and($upload->result_path)->toEndWith('.webp')
        ->and(storedImage('public', $upload->result_path)['mime'])->toBe('image/webp');
});

it('keeps a WebP as WebP', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.webp', 400, 400));

    runAvatarJob($upload);

    expect($upload->refresh()->result_mime_type)->toBe('image/webp');
});

it('scales an oversized image down inside the box without cropping', function (): void {
    $user = User::factory()->create();

    /* Deliberately not square: a crop would force it to one. */
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 2048, 1024));

    runAvatarJob($upload);
    $upload->refresh();

    [$width, $height] = storedImage('public', $upload->result_path);

    expect($width)->toBe(512)
        ->and($height)->toBe(256)
        ->and($width / $height)->toBe(2048 / 1024);
});

it('never enlarges an image that already fits', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 120, 90));

    runAvatarJob($upload);
    $upload->refresh();

    expect(storedImage('public', $upload->result_path))
        ->toMatchArray([0 => 120, 1 => 90]);
});

it('applies the box belonging to each branding asset', function (
    BrandingAsset $asset,
    int $expectedWidth,
    int $expectedHeight,
): void {
    $upload = stage(
        settingsManager(),
        UploadedFile::fake()->image('asset.png', 3000, 3000),
        ImageUploadTarget::Branding,
        $asset->value,
    );

    app()->call([new ProcessBrandingImageUpload($upload), 'handle']);
    $upload->refresh();

    [$width, $height] = storedImage('public', $upload->result_path);

    expect(max($width, $height))->toBeLessThanOrEqual(max($expectedWidth, $expectedHeight))
        ->and($width)->toBeLessThanOrEqual($expectedWidth)
        ->and($height)->toBeLessThanOrEqual($expectedHeight);
})->with([
    [BrandingAsset::Logo, 1200, 400],
    [BrandingAsset::Icon, 512, 512],
    [BrandingAsset::AuthBackground, 1920, 1080],
]);

it('removes the staged source once the image is published', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    Storage::disk('local')->assertExists($upload->source_path);

    runAvatarJob($upload);

    Storage::disk('local')->assertMissing($upload->source_path);
});

it('replaces the previous avatar only once the new one is stored', function (): void {
    $user = User::factory()->create();

    $first = stage($user, UploadedFile::fake()->image('one.png', 400, 400));
    runAvatarJob($first);
    $original = $user->refresh()->avatar_path;

    $second = stage($user, UploadedFile::fake()->image('two.png', 400, 400));
    runAvatarJob($second);
    $replacement = $user->refresh()->avatar_path;

    expect($replacement)->not->toBe($original);
    Storage::disk('public')->assertMissing($original);
    Storage::disk('public')->assertExists($replacement);
});

it('publishes nothing when the upload was cancelled first', function (): void {
    $user = User::factory()->create();
    $user->avatar_path = 'avatars/existing.png';
    $user->save();

    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    app(CancelImageUpload::class)->handle($upload);
    runAvatarJob($upload);

    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Cancelled)
        ->and($upload->result_path)->toBeNull()
        /* The existing avatar is exactly as it was. */
        ->and($user->refresh()->avatar_path)->toBe('avatars/existing.png');

    /* Nothing was written for a cancelled upload. */
    expect(Storage::disk('public')->allFiles('avatars'))->toBe([]);
});

it('keeps the existing avatar when processing fails for good', function (): void {
    $user = User::factory()->create();
    $user->avatar_path = 'avatars/existing.png';
    $user->save();

    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    /* Whatever the cause, the final failure path must be inert. */
    new ProcessAvatarImageUpload($upload)->failed(new RuntimeException('boom'));

    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Failed)
        ->and($upload->error_code)->toBe(FailImageUpload::REASON_PROCESSING)
        ->and($upload->lock_key)->toBeNull()
        ->and($user->refresh()->avatar_path)->toBe('avatars/existing.png');

    Storage::disk('local')->assertMissing($upload->source_path);
});

it('refuses to publish branding when the permission was revoked while queued', function (): void {
    $manager = settingsManager();
    $settings = app(BrandingSettings::class);
    $settings->logoPath = 'branding/logos/existing.png';
    $settings->save();

    $upload = stage(
        $manager,
        UploadedFile::fake()->image('logo.png', 1200, 400),
        ImageUploadTarget::Branding,
        BrandingAsset::Logo->value,
    );

    $manager->revokePermissionTo(Permission::ManageSettings->value);

    app()->call([new ProcessBrandingImageUpload($upload), 'handle']);
    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Failed)
        ->and($upload->error_code)->toBe(FailImageUpload::REASON_UNAUTHORIZED)
        ->and(app(BrandingSettings::class)->refresh()->logoPath)
        ->toBe('branding/logos/existing.png');

    /* A result that may not be published is deleted, not left for the sweep. */
    expect(Storage::disk('public')->allFiles('branding'))->toBe([]);
});

it('refuses to publish an avatar for an account that can no longer sign in', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    $user->status = UserStatus::Disabled;
    $user->suspended_until = null;
    $user->save();

    runAvatarJob($upload);
    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Failed)
        ->and($upload->error_code)->toBe(FailImageUpload::REASON_UNAUTHORIZED)
        ->and($user->refresh()->avatar_path)->toBeNull();
});

it('applies nothing at all when publication fails part-way through', function (): void {
    $user = User::factory()->create();

    $first = stage($user, UploadedFile::fake()->image('one.png', 400, 400));
    runAvatarJob($first);
    $original = (string) $user->refresh()->avatar_path;

    $second = stage($user, UploadedFile::fake()->image('two.png', 400, 400));

    /*
     * Fails after the new path is written and before the upload is completed —
     * the exact window in which a half-applied replacement would survive.
     */
    User::updated(function (): never {
        throw new RuntimeException('the write went through, the job did not');
    });

    expect(function () use ($second): void {
        runAvatarJob($second);
    })->toThrow(RuntimeException::class)
        ->and($user->refresh()->avatar_path)->toBe($original)
        ->and($second->refresh()->status)->not->toBe(ImageUploadStatus::Ready);

    /* The image the account is still pointing at has to be there. */
    Storage::disk('public')->assertExists($original);
});

it('publishes nothing a second time when a finished job is delivered again', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    runAvatarJob($upload);
    $published = (string) $user->refresh()->avatar_path;

    /* A duplicate delivery must find nothing left to do. */
    runAvatarJob($upload);

    expect($user->refresh()->avatar_path)->toBe($published);
    Storage::disk('public')->assertExists($published);
    expect(Storage::disk('public')->allFiles('avatars'))->toHaveCount(1);
});

it('refuses to complete an upload that was cancelled while it was processing', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    app(ClaimImageUpload::class)->handle($upload);
    app(CancelImageUpload::class)->handle($upload);

    $completed = app(CompleteImageUpload::class)
        ->handle($upload, 'avatars/whatever.png', 'image/png');

    expect($completed)->toBeFalse()
        ->and($upload->refresh()->status)->toBe(ImageUploadStatus::Cancelled)
        ->and($upload->result_path)->toBeNull();
});

it('completes an upload once and refuses to complete it again', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    app(ClaimImageUpload::class)->handle($upload);

    $complete = app(CompleteImageUpload::class);

    expect($complete->handle($upload, 'avatars/first.png', 'image/png'))->toBeTrue()
        /* A retry of the same completion may not move a finished upload. */
        ->and($complete->handle($upload, 'avatars/second.png', 'image/png'))->toBeFalse()
        ->and($upload->refresh()->result_path)->toBe('avatars/first.png');
});

it('keeps the previous image until the transaction replacing it commits', function (): void {
    Storage::disk('public')->put('avatars/previous.png', 'old bytes');
    Storage::disk('public')->put('avatars/replacement.png', 'new bytes');

    $swap = app(SwapStoredImagePath::class);

    try {
        DB::transaction(function () use ($swap): void {
            $swap->handle(
                'public',
                'avatars/replacement.png',
                'avatars/previous.png',
                function (): void {},
            );

            throw new RuntimeException('rolled back after the pointer moved');
        });
    } catch (RuntimeException) {
        /* The rollback is the point of the test. */
    }

    /* The restored pointer must not name a file that was already deleted. */
    Storage::disk('public')->assertExists('avatars/previous.png');
});

it('removes the previous image once the replacement is committed', function (): void {
    Storage::disk('public')->put('avatars/previous.png', 'old bytes');
    Storage::disk('public')->put('avatars/replacement.png', 'new bytes');

    DB::transaction(function (): void {
        app(SwapStoredImagePath::class)->handle(
            'public',
            'avatars/replacement.png',
            'avatars/previous.png',
            function (): void {},
        );
    });

    Storage::disk('public')->assertMissing('avatars/previous.png');
    Storage::disk('public')->assertExists('avatars/replacement.png');
});

it('does not overwrite a cancellation when the job later gives up', function (): void {
    $user = User::factory()->create();
    $upload = stage($user, UploadedFile::fake()->image('avatar.png', 400, 400));

    app(CancelImageUpload::class)->handle($upload);
    new ProcessAvatarImageUpload($upload)->failed(new RuntimeException('boom'));

    expect($upload->refresh()->status)->toBe(ImageUploadStatus::Cancelled);
});
