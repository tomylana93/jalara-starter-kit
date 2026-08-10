<?php

use App\Actions\Media\FailImageUpload;
use App\Actions\Media\StageImageUpload;
use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Jobs\Media\ProcessDocumentationImageUpload;
use App\Models\ImageUpload;
use App\Models\User;
use App\Support\DocumentationContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

function documentationImage(int $width = 800, int $height = 600): UploadedFile
{
    return UploadedFile::fake()->image('diagram.png', $width, $height);
}

function stageDocumentationImage(User $author, ?UploadedFile $file = null): ImageUpload
{
    return app(StageImageUpload::class)->handle(
        $author,
        $file ?? documentationImage(),
        ImageUploadTarget::DocumentationImage,
    );
}

function runDocumentationImageJob(ImageUpload $upload): void
{
    app()->call([new ProcessDocumentationImageUpload($upload), 'handle']);
}

it('accepts an image from a super administrator and answers with a pollable record', function (): void {
    Queue::fake();

    $response = actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('documentation.manage.images.store'), ['image' => documentationImage()])
        ->assertStatus(202)
        ->assertJsonPath('data.status', ImageUploadStatus::Pending->value)
        ->assertJsonPath('data.target', ImageUploadTarget::DocumentationImage->value)
        /* Nothing to link to until the queue has published something. */
        ->assertJsonPath('data.url', null);

    $upload = ImageUpload::query()->sole();

    expect($response->json('data.poll_url'))
        ->toBe(route('media.image-uploads.show', $upload))
        ->and($response->json('data.cancel_url'))
        ->toBe(route('media.image-uploads.destroy', $upload));

    Queue::assertPushed(ProcessDocumentationImageUpload::class);
});

it('lets one author upload several images at once', function (): void {
    Queue::fake();

    $admin = userWithRole(Role::SuperAdmin);

    actingAs($admin)
        ->post(route('documentation.manage.images.store'), ['image' => documentationImage()])
        ->assertStatus(202);

    /*
     * A document may hold many images, so the target is deliberately not
     * exclusive: a second upload must not collide with the first.
     */
    actingAs($admin)
        ->post(route('documentation.manage.images.store'), ['image' => documentationImage()])
        ->assertStatus(202);

    expect(ImageUpload::query()->count())->toBe(2)
        ->and(ImageUpload::query()->whereNotNull('lock_key')->count())->toBe(0);
});

it('refuses an upload from anyone who may not write documentation', function (): void {
    Queue::fake();

    post(route('documentation.manage.images.store'), ['image' => documentationImage()])
        ->assertRedirect(route('login'));

    actingAs(User::factory()->create())
        ->post(route('documentation.manage.images.store'), ['image' => documentationImage()])
        ->assertForbidden();

    expect(ImageUpload::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('rejects files that are not an accepted image', function (UploadedFile $file): void {
    Queue::fake();

    actingAs(userWithRole(Role::SuperAdmin))
        ->post(route('documentation.manage.images.store'), ['image' => $file], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('image');

    expect(ImageUpload::query()->count())->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'a document pretending to be an image' => fn () => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    'an unsupported image format' => fn () => UploadedFile::fake()->image('animation.gif', 100, 100),
    'a file over the size limit' => fn () => UploadedFile::fake()->image('huge.png', 800, 600)
        ->size(DocumentationContent::IMAGE_MAX_KILOBYTES + 1),
    'an image beyond the dimension limit' => fn () => UploadedFile::fake()->image(
        'wide.png',
        DocumentationContent::IMAGE_MAX_DIMENSION + 1,
        100,
    ),
]);

it('publishes into the managed documentation prefix and exposes its url', function (): void {
    $admin = userWithRole(Role::SuperAdmin);
    $upload = stageDocumentationImage($admin);

    runDocumentationImageJob($upload);
    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Ready)
        ->and($upload->result_path)->toStartWith(DocumentationContent::IMAGE_DIRECTORY.'/');

    Storage::disk('public')->assertExists($upload->result_path);
    /* The staged source is consumed once the result is committed. */
    Storage::disk('local')->assertMissing($upload->source_path);

    $url = actingAs($admin)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertOk()
        ->assertJsonPath('data.status', ImageUploadStatus::Ready->value)
        ->json('data.url');

    expect($url)->toBe(Storage::disk('public')->url($upload->result_path));
});

it('scales a large image down into the documentation box', function (): void {
    $upload = stageDocumentationImage(
        userWithRole(Role::SuperAdmin),
        UploadedFile::fake()->image('diagram.png', 2048, 1024),
    );

    runDocumentationImageJob($upload);
    $upload->refresh();

    $size = getimagesizefromstring((string) Storage::disk('public')->get($upload->result_path));

    throw_if($size === false, RuntimeException::class, 'The published documentation image is not readable.');

    expect($size[0])->toBe(1600)
        ->and($size[1])->toBe(800);
});

it('refuses to publish when the writing role was revoked while queued', function (): void {
    $admin = userWithRole(Role::SuperAdmin);
    $upload = stageDocumentationImage($admin);

    $admin->removeRole(Role::SuperAdmin->value);

    runDocumentationImageJob($upload);
    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Failed)
        ->and($upload->error_code)->toBe(FailImageUpload::REASON_UNAUTHORIZED)
        ->and($upload->result_path)->toBeNull();

    /* A result that may not be published is deleted, not left for the sweep. */
    expect(Storage::disk('public')->allFiles(DocumentationContent::IMAGE_DIRECTORY))->toBe([]);
});

it('refuses to publish for an account that can no longer sign in', function (): void {
    $admin = userWithRole(Role::SuperAdmin);
    $upload = stageDocumentationImage($admin);

    $admin->status = UserStatus::Disabled;
    $admin->suspended_until = null;
    $admin->save();

    runDocumentationImageJob($upload);

    expect($upload->refresh()->status)->toBe(ImageUploadStatus::Failed)
        ->and($upload->error_code)->toBe(FailImageUpload::REASON_UNAUTHORIZED);
});
