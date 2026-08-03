<?php

use App\Enums\BrandingAsset;
use App\Enums\ImageUploadStatus;
use App\Enums\ImageUploadTarget;
use App\Jobs\Media\ProcessAvatarImageUpload;
use App\Jobs\Media\ProcessBrandingImageUpload;
use App\Jobs\Media\ProcessChatImageUpload;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\ImageUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');

    /* Uploads must stay pending for the acceptance contract to be observable. */
    Queue::fake();
});

function squareImage(): UploadedFile
{
    return UploadedFile::fake()->image('image.png', 512, 512);
}

it('accepts an avatar upload and answers with a pollable record', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->post(route('account.profile.avatar.store'), ['image' => squareImage()])
        ->assertStatus(202)
        ->assertJsonPath('data.status', ImageUploadStatus::Pending->value)
        ->assertJsonPath('data.target', ImageUploadTarget::Avatar->value);

    $upload = ImageUpload::query()->sole();

    expect($response->json('data.poll_url'))
        ->toBe(route('media.image-uploads.show', $upload))
        ->and($response->json('data.cancel_url'))
        ->toBe(route('media.image-uploads.destroy', $upload));

    Queue::assertPushed(ProcessAvatarImageUpload::class);

    /* The avatar itself is untouched until the queue publishes one. */
    expect($user->refresh()->avatar_path)->toBeNull();
});

it('stages the source privately and never exposes it', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->post(route('account.profile.avatar.store'), ['image' => squareImage()]);

    $upload = ImageUpload::query()->sole();

    Storage::disk('local')->assertExists($upload->source_path);
    Storage::disk('public')->assertMissing($upload->source_path);

    expect($upload->source_path)->toStartWith(ImageUpload::SOURCE_DIRECTORY.'/')
        ->and($response->json('data'))
        ->not->toHaveKeys(['source_path', 'payload', 'lock_key', 'result_path']);
});

it('lets the owner poll an upload and refuses everyone else', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    actingAs($owner)->post(route('account.profile.avatar.store'), ['image' => squareImage()]);
    $upload = ImageUpload::query()->sole();

    actingAs($owner)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertOk()
        ->assertJsonPath('data.id', $upload->getKey());

    actingAs($stranger)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertForbidden();
});

it('hands a ready chat upload its message and conversation', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = Message::factory()->withImage()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
    ]);

    $upload = ImageUpload::factory()
        ->for($sender)
        ->chatImage()
        ->ready(resultPath: $message->image_path)
        ->create(['target_key' => $message->id]);

    actingAs($sender)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertOk()
        ->assertJsonPath('data.message.id', $message->id)
        ->assertJsonPath('data.message.conversation_id', $conversation->id)
        ->assertJsonPath('data.conversation.id', $conversation->id)
        ->assertJsonPath('data.conversation.participant.id', $recipient->id)
        ->assertJsonPath('data.conversation.last_message.id', $message->id)
        /* Chat images stay private; they are reached through the message. */
        ->assertJsonPath('data.url', null);

    /* Even holding the identifier, nobody else may read the result. */
    actingAs($recipient)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertForbidden();
});

it('answers with no result when the produced message is gone', function (): void {
    $sender = User::factory()->create();

    $upload = ImageUpload::factory()
        ->for($sender)
        ->chatImage()
        ->ready(resultPath: 'chat/removed.webp')
        ->create(['target_key' => (string) Str::uuid7()]);

    actingAs($sender)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertOk()
        ->assertJsonPath('data.message', null)
        ->assertJsonPath('data.conversation', null);
});

it('lets the owner cancel an upload and refuses everyone else', function (): void {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    actingAs($owner)->post(route('account.profile.avatar.store'), ['image' => squareImage()]);
    $upload = ImageUpload::query()->sole();

    actingAs($stranger)
        ->deleteJson(route('media.image-uploads.destroy', $upload))
        ->assertForbidden();

    expect($upload->refresh()->status)->toBe(ImageUploadStatus::Pending);

    actingAs($owner)
        ->deleteJson(route('media.image-uploads.destroy', $upload))
        ->assertOk()
        ->assertJsonPath('data.status', ImageUploadStatus::Cancelled->value);

    $upload->refresh();

    expect($upload->status)->toBe(ImageUploadStatus::Cancelled)
        ->and($upload->lock_key)->toBeNull();

    /* Cancelling releases the staged bytes rather than waiting for the sweep. */
    Storage::disk('local')->assertMissing($upload->source_path);
});

it('refuses a second upload while an avatar upload is active', function (): void {
    $user = User::factory()->create();

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => squareImage()])
        ->assertStatus(202);

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => squareImage()])
        ->assertStatus(409)
        ->assertJsonPath('data.status', ImageUploadStatus::Pending->value);

    expect(ImageUpload::query()->count())->toBe(1);

    /* A refused upload leaves nothing staged behind. */
    expect(Storage::disk('local')->allFiles(ImageUpload::SOURCE_DIRECTORY))->toHaveCount(1);
});

it('refuses a second branding upload even from another administrator', function (): void {
    $first = settingsManager();
    $second = settingsManager();

    actingAs($first)
        ->post(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertStatus(202);

    $response = actingAs($second)
        ->postJson(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertStatus(409);

    /*
     * The upload in the way is not this administrator's, and its endpoints
     * answer only its owner. Handing them over would promise a conversation
     * that can only end in 403, so the conflict carries nothing to follow.
     */
    $response->assertJsonMissingPath('data')
        ->assertJsonStructure(['message']);

    expect($response->json())->not->toHaveKeys(['data', 'poll_url', 'cancel_url'])
        ->and(ImageUpload::query()->count())->toBe(1);
});

it('keeps another administrator out of the upload that blocked them', function (): void {
    $owner = settingsManager();
    $other = settingsManager();

    actingAs($owner)
        ->post(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertStatus(202);

    $upload = ImageUpload::query()->firstOrFail();

    /* Even holding the identifier, the blocked administrator may not watch it. */
    actingAs($other)->getJson(route('media.image-uploads.show', $upload))->assertForbidden();
    actingAs($other)->deleteJson(route('media.image-uploads.destroy', $upload))->assertForbidden();

    expect($upload->refresh()->status)->toBe(ImageUploadStatus::Pending);
});

it('hands a branding upload back to the administrator who started it', function (): void {
    $manager = settingsManager();

    actingAs($manager)
        ->post(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertStatus(202);

    $upload = ImageUpload::query()->firstOrFail();

    /* Their own upload, most likely from another tab: theirs to resume. */
    actingAs($manager)
        ->postJson(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertStatus(409)
        ->assertJsonPath('data.id', $upload->id)
        ->assertJsonPath('data.status', ImageUploadStatus::Pending->value)
        ->assertJsonStructure(['data' => ['poll_url', 'cancel_url']]);
});

it('allows a different branding asset while one is active', function (): void {
    $manager = settingsManager();

    actingAs($manager)
        ->post(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertStatus(202);

    actingAs($manager)
        ->post(route('settings.branding.asset.store', BrandingAsset::Icon->value), [
            'image' => squareImage(),
        ])
        ->assertStatus(202);

    expect(ImageUpload::query()->count())->toBe(2);
});

it('lets several chat messages carry an image at once', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    foreach (range(1, 2) as $ignored) {
        actingAs($sender)
            ->postJson(route('chat.messages.store'), [
                'recipient_id' => $recipient->id,
                'image' => UploadedFile::fake()->image('photo.png', 640, 480),
            ])
            ->assertStatus(202);
    }

    expect(ImageUpload::query()->count())->toBe(2)
        ->and(ImageUpload::query()->whereNotNull('lock_key')->count())->toBe(0);

    Queue::assertPushed(ProcessChatImageUpload::class, 2);
});

it('restores only the callers own active uploads', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    actingAs($owner)->post(route('account.profile.avatar.store'), ['image' => squareImage()]);
    actingAs($other)->post(route('account.profile.avatar.store'), ['image' => squareImage()]);

    $terminal = ImageUpload::factory()->for($owner)->cancelled()->create();

    $response = actingAs($owner)
        ->getJson(route('media.image-uploads.index'))
        ->assertOk();

    /** @var list<array{id: string}> $records */
    $records = $response->json('data');
    $ids = array_column($records, 'id');

    expect($ids)->toHaveCount(1)
        ->and($ids)->not->toContain($terminal->getKey());
});

it('requires authentication for every status endpoint', function (): void {
    $upload = ImageUpload::factory()->create();

    $this->getJson(route('media.image-uploads.index'))->assertUnauthorized();
    $this->getJson(route('media.image-uploads.show', $upload))->assertUnauthorized();
    $this->deleteJson(route('media.image-uploads.destroy', $upload))->assertUnauthorized();
});

it('keeps branding uploads behind the settings permission', function (): void {
    actingAs(User::factory()->create())
        ->post(route('settings.branding.asset.store', BrandingAsset::Logo->value), [
            'image' => UploadedFile::fake()->image('logo.png', 1200, 400),
        ])
        ->assertForbidden();

    expect(ImageUpload::query()->count())->toBe(0);
    Queue::assertNotPushed(ProcessBrandingImageUpload::class);
});

it('eager loads participant roles to avoid N+1 queries during serialization', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = Message::factory()->withImage()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
    ]);

    $upload = ImageUpload::factory()
        ->for($sender)
        ->chatImage()
        ->ready(resultPath: $message->image_path)
        ->create(['target_key' => $message->id]);

    DB::enableQueryLog();

    actingAs($sender)
        ->getJson(route('media.image-uploads.show', $upload))
        ->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $roleQueries = array_filter($queries, function (array $query): bool {
        return str_contains($query['query'], 'roles') || str_contains($query['query'], 'model_has_roles');
    });

    $eagerLoadedQueries = array_filter($roleQueries, function (array $query) use ($recipient): bool {
        return in_array($recipient->id, $query['bindings'], true);
    });

    expect($eagerLoadedQueries)->toHaveCount(1);

    $eagerQuery = reset($eagerLoadedQueries);
    expect($eagerQuery)->toBeArray();
    if (is_array($eagerQuery)) {
        expect($eagerQuery['bindings'])->toContain($sender->id);
    }

    expect($roleQueries)->toHaveCount(2);
});
