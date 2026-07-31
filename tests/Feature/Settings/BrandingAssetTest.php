<?php

use App\Actions\Media\ReplaceStoredImage;
use App\Enums\BrandingAsset;
use App\Models\User;
use App\Settings\BrandingSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Build a valid upload for the given asset.
 */
function assetImage(BrandingAsset $asset): UploadedFile
{
    return match ($asset) {
        BrandingAsset::Logo, BrandingAsset::LogoDark => UploadedFile::fake()->image('logo.png', 1200, 400),
        BrandingAsset::Icon, BrandingAsset::IconDark => UploadedFile::fake()->image('icon.png', 512, 512),
        BrandingAsset::AuthBackground => UploadedFile::fake()->image('background.png', 1920, 1080),
    };
}

it('stores an image for every branding asset', function (BrandingAsset $asset) {
    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', $asset->value), [
            'image' => assetImage($asset),
        ])
        ->assertRedirect(route('settings.branding.edit'));

    $path = app(BrandingSettings::class)->{$asset->property()};

    expect($path)->toStartWith($asset->directory().'/')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
})->with(BrandingAsset::cases());

it('removes the previous file when an asset is replaced', function () {
    $manager = settingsManager();

    actingAs($manager)->post(route('settings.branding.asset.store', 'logo'), [
        'image' => assetImage(BrandingAsset::Logo),
    ]);

    $original = app(BrandingSettings::class)->logoPath;

    actingAs($manager)->post(route('settings.branding.asset.store', 'logo'), [
        'image' => assetImage(BrandingAsset::Logo),
    ]);

    $replacement = app(BrandingSettings::class)->logoPath;

    expect($replacement)->not->toBe($original)
        ->and(Storage::disk('public')->exists($original))->toBeFalse()
        ->and(Storage::disk('public')->exists($replacement))->toBeTrue();
});

it('clears the setting and deletes the file when an asset is removed', function () {
    $manager = settingsManager();

    actingAs($manager)->post(route('settings.branding.asset.store', 'icon'), [
        'image' => assetImage(BrandingAsset::Icon),
    ]);

    $path = app(BrandingSettings::class)->iconPath;

    actingAs($manager)
        ->delete(route('settings.branding.asset.destroy', 'icon'))
        ->assertRedirect(route('settings.branding.edit'));

    expect(app(BrandingSettings::class)->iconPath)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('rejects a disallowed file type', function () {
    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', 'logo'), [
            'image' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');

    expect(app(BrandingSettings::class)->logoPath)->toBeNull();
});

it('rejects an svg even though it is an image', function () {
    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', 'icon'), [
            'image' => UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('image');
});

it('rejects a file above the size limit', function () {
    $oversized = UploadedFile::fake()->image('logo.png', 1200, 400)->size(3 * 1024);

    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', 'logo'), ['image' => $oversized])
        ->assertSessionHasErrors('image');
});

it('rejects an icon that is not square', function () {
    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', 'icon'), [
            'image' => UploadedFile::fake()->image('icon.png', 512, 256),
        ])
        ->assertSessionHasErrors('image');
});

it('rejects an image beyond the maximum dimensions', function () {
    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', 'logo'), [
            'image' => UploadedFile::fake()->image('logo.png', 4000, 400),
        ])
        ->assertSessionHasErrors('image');
});

it('rejects an unknown asset', function () {
    actingAs(settingsManager())
        ->post(route('settings.branding.asset.store', 'wordmark'), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertNotFound();
});

it('requires the manage settings permission', function () {
    actingAs(User::factory()->create())
        ->post(route('settings.branding.asset.store', 'logo'), [
            'image' => assetImage(BrandingAsset::Logo),
        ])
        ->assertForbidden();

    actingAs(User::factory()->create())
        ->delete(route('settings.branding.asset.destroy', 'logo'))
        ->assertForbidden();
});

it('requires authentication', function () {
    $this->post(route('settings.branding.asset.store', 'logo'))->assertRedirect(route('login'));
    $this->delete(route('settings.branding.asset.destroy', 'logo'))->assertRedirect(route('login'));
});

it('shares public urls and never the stored paths', function () {
    $manager = settingsManager();

    actingAs($manager)->post(route('settings.branding.asset.store', 'icon'), [
        'image' => assetImage(BrandingAsset::Icon),
    ]);

    $path = app(BrandingSettings::class)->iconPath;

    actingAs($manager)
        ->get(route('settings.branding.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('branding.iconUrl', Storage::disk('public')->url($path))
            ->missing('branding.iconPath')
            ->missing('settings.iconPath'),
        );
});

it('falls back to null urls when no image is stored', function () {
    actingAs(settingsManager())
        ->get(route('settings.branding.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('branding.logoUrl', null)
            ->where('branding.logoDarkUrl', null)
            ->where('branding.iconUrl', null)
            ->where('branding.iconDarkUrl', null)
            ->where('branding.authBackgroundUrl', null),
        );
});

it('deletes the newly written file when persistence fails', function () {
    $file = UploadedFile::fake()->image('logo.png', 1200, 400);

    expect(fn () => app(ReplaceStoredImage::class)->handle(
        $file,
        'branding/logos',
        null,
        fn () => throw new RuntimeException('persistence failed'),
    ))->toThrow(RuntimeException::class)
        ->and(Storage::disk('public')->allFiles('branding/logos'))->toBeEmpty();
});

it('keeps the previous file when persistence fails during a replacement', function () {
    $previous = Storage::disk('public')->putFile('branding/logos', UploadedFile::fake()->image('old.png', 1200, 400));

    throw_if($previous === false, RuntimeException::class, 'The replacement fixture could not be stored.');

    expect(fn () => app(ReplaceStoredImage::class)->handle(
        UploadedFile::fake()->image('new.png', 1200, 400),
        'branding/logos',
        $previous,
        fn () => throw new RuntimeException('persistence failed'),
    ))->toThrow(RuntimeException::class)
        ->and(Storage::disk('public')->exists($previous))->toBeTrue()
        ->and(Storage::disk('public')->allFiles('branding/logos'))->toHaveCount(1);
});
