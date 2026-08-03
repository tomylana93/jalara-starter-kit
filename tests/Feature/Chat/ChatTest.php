<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Media\FailImageUpload;
use App\Actions\Media\ProcessQueuedImageUpload;
use App\Enums\ImageUploadStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Events\Chat\ChatConversationRead;
use App\Events\Chat\ChatMessageSent;
use App\Http\Controllers\Chat\ChatController;
use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Jobs\Media\ProcessChatImageUpload;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\Chat\Reaction;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function (): void {
    Event::fake([ChatMessageSent::class, ChatConversationRead::class]);
    Queue::fake();
});

test('the chat page renders the first page of the inbox', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $peer->id,
        'body' => 'Hello there',
    ]);

    actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->has('conversations.data', 1)
            ->where('conversations.data.0.id', $conversation->id)
            ->where('conversations.data.0.participant.name', $peer->name)
            ->where('conversations.data.0.unread_count', 1),
        );
});

test('the inbox reads every participant role in one query', function (): void {
    $user = User::factory()->create();

    $openConversationWith = function (User $peer) use ($user): void {
        $conversation = Conversation::factory()->between($user, $peer)->create();

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $peer->id,
            'body' => 'Hello',
        ]);
    };

    $openConversationWith(userWithRole(Role::User));

    $response = null;
    $inbox = function () use ($user, &$response): void {
        $response = actingAs($user)->getJson(route('chat.conversations.index'))->assertOk();
    };

    /* Warms the viewer's own role lookup, which is not part of the payload. */
    $inbox();

    $single = roleQueryCount($inbox);

    expect($single)->toBe(1)
        ->and($response->json('data.0.participant.role'))->toBe(Role::User->label());

    $openConversationWith(userWithRole(Role::User));
    $openConversationWith(userWithRole(Role::User));

    /* Three counterparts cost what one did: the roles come back in one load. */
    expect(roleQueryCount($inbox))->toBe($single)
        ->and($response->json('data'))->toHaveCount(3);
});

test('the inbox never exposes a conversation the viewer is not part of', function (): void {
    $user = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    Conversation::factory()->between($first, $second)->create();

    actingAs($user)
        ->getJson(route('chat.conversations.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a conversation is created only when the first valid message is sent', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'body' => '   ',
        ])
        ->assertStatus(422);

    expect(Conversation::query()->count())->toBe(0);

    $response = actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'body' => "First line\nSecond line",
        ])
        ->assertStatus(201);

    expect(Conversation::query()->count())->toBe(1)
        ->and(Message::query()->value('body'))->toBe("First line\nSecond line");

    $conversationId = $response->json('conversation.id');

    expect(Participant::query()->where('conversation_id', $conversationId)->count())->toBe(2);

    Event::assertDispatched(ChatMessageSent::class);
    Queue::assertPushed(DeliverChatMessageNotification::class);
});

test('a second message reuses the pair canonical conversation', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), ['recipient_id' => $recipient->id, 'body' => 'One'])
        ->assertStatus(201);

    /* The other side answering must not open a second direct message. */
    actingAs($recipient)
        ->postJson(route('chat.messages.store'), ['recipient_id' => $sender->id, 'body' => 'Two'])
        ->assertStatus(201);

    expect(Conversation::query()->count())->toBe(1)
        ->and(Message::query()->count())->toBe(2);
});

test('a message longer than the maximum is rejected', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'body' => str_repeat('a', Message::MAX_LENGTH + 1),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('body');

    expect(Message::query()->count())->toBe(0);
});

test('a message at the maximum length is accepted', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'body' => str_repeat('a', Message::MAX_LENGTH),
        ])
        ->assertStatus(201);
});

test('a message may contain one private image without text', function (): void {
    Storage::fake('local');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    /*
     * An image message is only accepted here; it is created once the queue has
     * processed the image, so the job is run explicitly.
     */
    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('photo.png', 640, 480),
        ])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending');

    expect(Message::query()->exists())->toBeFalse();
    Queue::assertPushed(ProcessChatImageUpload::class);

    $upload = ImageUpload::query()->firstOrFail();
    app()->call([new ProcessChatImageUpload($upload), 'handle']);

    $message = Message::query()->firstOrFail();

    expect($message->body)->toBeNull()
        ->and($message->image_mime_type)->toBe('image/png')
        ->and($upload->refresh()->status)->toBe(ImageUploadStatus::Ready);
    Storage::disk('local')->assertExists((string) $message->image_path);
});

test('a message is announced only once the transaction holding it commits', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    DB::transaction(function () use ($conversation, $sender): void {
        app(SendMessage::class)->handle($conversation, $sender, 'still uncommitted');

        /*
         * The row exists but only inside this transaction. Announcing it here
         * would show every client a message a rollback could still erase.
         */
        Event::assertNotDispatched(ChatMessageSent::class);
        Queue::assertNotPushed(DeliverChatMessageNotification::class);
    });

    Event::assertDispatched(ChatMessageSent::class);
    Queue::assertPushed(DeliverChatMessageNotification::class);
});

test('a failed image publication leaves no message, no conversation and no announcement', function (): void {
    Storage::fake('local');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('photo.png', 640, 480),
        ])
        ->assertStatus(202);

    $upload = ImageUpload::query()->firstOrFail();

    /* Fails after the row is written — the window a rollback has to cover. */
    Message::created(function (): never {
        throw new RuntimeException('the write went through, the job did not');
    });

    expect(fn (): mixed => app()->call([new ProcessChatImageUpload($upload), 'handle']))
        ->toThrow(RuntimeException::class)
        ->and(Message::query()->exists())->toBeFalse()
        ->and(Conversation::query()->exists())->toBeFalse();

    /* Nothing may be announced for a message that does not exist. */
    Event::assertNotDispatched(ChatMessageSent::class);
    Queue::assertNotPushed(DeliverChatMessageNotification::class);
});

test('an image message retried after a failure is created exactly once', function (): void {
    Storage::fake('local');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('photo.png', 640, 480),
        ])
        ->assertStatus(202);

    $upload = ImageUpload::query()->firstOrFail();
    $failing = true;

    Message::created(function () use (&$failing): void {
        throw_if($failing, RuntimeException::class, 'first attempt');
    });

    expect(fn (): mixed => app()->call([new ProcessChatImageUpload($upload), 'handle']))
        ->toThrow(RuntimeException::class);

    /* The retry the queue would make once the transient cause has cleared. */
    $failing = false;
    app()->call([new ProcessChatImageUpload($upload), 'handle']);

    expect(Message::query()->count())->toBe(1)
        ->and(Conversation::query()->count())->toBe(1)
        ->and($upload->refresh()->status)->toBe(ImageUploadStatus::Ready);

    Event::assertDispatchedTimes(ChatMessageSent::class, 1);
    Queue::assertPushed(DeliverChatMessageNotification::class, 1);
});

test('a chat image obeys type size dimension and feature toggle rules', function (): void {
    Storage::fake('local');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('wide.png', Message::IMAGE_MAX_DIMENSION + 1, 10),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()
                ->image('large.png', 10, 10)
                ->size(Message::IMAGE_MAX_KILOBYTES + 1),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => [
                UploadedFile::fake()->image('first.png', 10, 10),
                UploadedFile::fake()->image('second.png', 10, 10),
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    $settings = app(ChatSettings::class);
    $settings->imageUploadsEnabled = false;
    $settings->save();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('blocked.webp', 10, 10),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(Message::query()->count())->toBe(0);
});

test('a peer can add replace and remove their single reaction', function (): void {
    $sender = User::factory()->create();
    $peer = User::factory()->create();
    $conversation = Conversation::factory()->between($sender, $peer)->create();
    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
    ]);

    actingAs($peer)
        ->putJson(route('chat.messages.reaction.update', $message), ['emoji' => '👍'])
        ->assertOk()
        ->assertJsonPath('reaction.emoji', '👍');

    actingAs($peer)
        ->putJson(route('chat.messages.reaction.update', $message), ['emoji' => '🔥'])
        ->assertOk()
        ->assertJsonPath('reaction.emoji', '🔥');

    expect(Reaction::query()->count())->toBe(1);

    actingAs($peer)
        ->deleteJson(route('chat.messages.reaction.destroy', $message))
        ->assertOk()
        ->assertJsonPath('reaction', null);

    expect(Reaction::query()->count())->toBe(0);
});

test('the sender cannot open a conversation with themselves', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::User->value, 'web'));

    /* Warms the viewer's own role lookup, which the audit and middleware read. */
    actingAs($user)->getJson(route('chat.conversations.index'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        actingAs($user)
            ->postJson(route('chat.messages.store'), [
                'recipient_id' => $user->id,
                'body' => 'Hello',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recipient_id');

        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    expect(Conversation::query()->count())->toBe(0);

    $roleQueries = array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles')
    );

    foreach ($roleQueries as $query) {
        expect($query['bindings'])->not->toContain($user->id);
    }
});

test('the recipient directory answers only Active users matched by name', function (): void {
    $user = User::factory()->create();
    $match = User::factory()->create(['name' => 'Amelia Stone']);
    $disabled = User::factory()->disabled()->create(['name' => 'Amelia Frost']);
    User::factory()->create(['name' => 'Bruno Vega']);

    $match->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::User->value, 'web'));

    $response = actingAs($user)
        ->getJson(route('chat.recipients.index', ['search' => 'Amelia']))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.id'))->toBe($match->id)
        ->and($response->json('data.0.role'))->toBe(Role::User->label())
        ->and($response->json('data.0'))->not->toHaveKey('email');

    unset($disabled);
});

test('a search term shorter than two characters is rejected', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson(route('chat.recipients.index', ['search' => 'a']))
        ->assertStatus(422);
});

test('the widget message window returns the newest thirty and scrolls into history', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();

    /* Distinct timestamps so the window has one unambiguous order. */
    foreach (range(1, 35) as $index) {
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $peer->id,
            'body' => 'Message '.$index,
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $first = actingAs($user)
        ->getJson(route('chat.conversations.show', $conversation))
        ->assertOk();

    expect($first->json('messages'))->toHaveCount(Message::WINDOW)
        ->and($first->json('messages.0.body'))->toBe('Message 6')
        ->and($first->json('hasMore'))->toBeTrue();

    $older = actingAs($user)
        ->getJson(route('chat.conversations.show', [
            'conversation' => $conversation->id,
            'before' => $first->json('messages.0.id'),
        ]))
        ->assertOk();

    expect($older->json('messages'))->toHaveCount(5)
        ->and($older->json('messages.0.body'))->toBe('Message 1')
        ->and($older->json('hasMore'))->toBeFalse();
});

test('the transcript is paged newest first under its own page name', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();

    foreach (range(1, 45) as $index) {
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $peer->id,
            'body' => 'Message '.$index,
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $live = actingAs($user)
        ->get(route('chat.index', ['conversation' => $conversation->id]))
        ->assertOk();

    $livePage = $live->viewData('page')['props']['messages']['data'];

    expect($livePage)->toHaveCount(Message::WINDOW);
    /* Newest first: the client reverses the window for display. */
    expect($livePage[0]['body'])->toBe('Message 45');
    expect($livePage[29]['body'])->toBe('Message 16');
});

test('an older transcript page is a distinct window that never replaces the visible one', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();

    foreach (range(1, 45) as $index) {
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $peer->id,
            'body' => 'Message '.$index,
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $live = actingAs($user)->get(route('chat.index', ['conversation' => $conversation->id]));
    $visible = collect(inertiaRows($live->viewData('page')['props']['messages']['data']))->pluck('id');

    /*
     * What reverse infinite scroll asks the server for when the reader reaches
     * the top: the next page under the transcript's own page name.
     */
    $older = actingAs($user)
        ->get(route('chat.index', [
            'conversation' => $conversation->id,
            ChatController::MESSAGES_PAGE => 2,
        ]))
        ->assertOk();

    $olderPage = $older->viewData('page')['props']['messages']['data'];
    $prepended = collect(inertiaRows($olderPage))->pluck('id');

    expect($prepended)->toHaveCount(15);

    /* Disjoint windows: nothing already on screen is sent again or replaced. */
    expect($prepended->intersect($visible)->all())->toBe([]);

    /* And it is genuinely older history, ready to be prepended. */
    expect($olderPage[0]['body'])->toBe('Message 15');
    expect($olderPage[14]['body'])->toBe('Message 1');
});

test('the inbox and the transcript page independently', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 25) as $index) {
        $peer = User::factory()->create(['name' => 'Peer '.$index]);
        $conversation = Conversation::factory()->between($user, $peer)->create([
            'last_message_at' => now()->addSeconds($index),
        ]);
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $peer->id,
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $first = actingAs($user)->get(route('chat.index'));

    expect($first->viewData('page')['props']['conversations']['data'])
        ->toHaveCount(Conversation::PER_PAGE);

    $second = actingAs($user)
        ->get(route('chat.index', [ChatController::CONVERSATIONS_PAGE => 2]));

    expect($second->viewData('page')['props']['conversations']['data'])->toHaveCount(5);
});

test('reading a conversation moves the receipt and never moves it backwards', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();

    $older = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $peer->id,
        'created_at' => now()->subMinute(),
    ]);

    $newer = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $peer->id,
        'created_at' => now(),
    ]);

    actingAs($user)
        ->postJson(route('chat.conversations.read', $conversation), ['message_id' => $newer->id])
        ->assertOk();

    $participant = Participant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->firstOrFail();

    expect($participant->last_read_at->timestamp)->toBe($newer->created_at->timestamp);

    /* Scrolling back through history must not undo the receipt. */
    actingAs($user)
        ->postJson(route('chat.conversations.read', $conversation), ['message_id' => $older->id])
        ->assertOk();

    expect($participant->fresh()->last_read_at->timestamp)->toBe($newer->created_at->timestamp);

    Event::assertDispatchedTimes(ChatConversationRead::class, 1);
});

test('the inbox orders conversations by the most recent activity', function (): void {
    $user = User::factory()->create();
    $quiet = User::factory()->create();
    $recent = User::factory()->create();

    Conversation::factory()->between($user, $quiet)->create(['last_message_at' => now()->subDay()]);
    $latest = Conversation::factory()->between($user, $recent)->create(['last_message_at' => now()]);

    $response = actingAs($user)
        ->getJson(route('chat.conversations.index'))
        ->assertOk();

    expect($response->json('data.0.id'))->toBe($latest->id);
});

test('a sender is throttled after thirty messages in a minute', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    foreach (range(1, 30) as $index) {
        actingAs($sender)
            ->postJson(route('chat.messages.store'), [
                'conversation_id' => $conversation->id,
                'body' => 'Message '.$index,
            ])
            ->assertStatus(201);
    }

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'conversation_id' => $conversation->id,
            'body' => 'One too many',
        ])
        ->assertStatus(429);

    expect(Message::query()->count())->toBe(30);
});

test('a guest cannot reach any chat surface', function (): void {
    get(route('chat.index'))->assertRedirect(route('login'));
    post(route('chat.messages.store'))->assertRedirect(route('login'));
});

test('authorized single conversation show returns counterpart role and loads both participant roles in one query', function (): void {
    $user = userWithRole(Role::User);
    $peer = userWithRole(Role::User);

    $conversation = Conversation::factory()->between($user, $peer)->create();

    // Warm the user's roles lookup (for authorization, etc.)
    actingAs($user)->getJson(route('chat.conversations.show', $conversation))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $response = actingAs($user)
            ->getJson(route('chat.conversations.show', $conversation))
            ->assertOk();

        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    // Check that role label is present
    expect($response->json('conversation.participant.role'))->toBe(Role::User->label());

    // Check that there is a query on model_has_roles containing both user and peer IDs.
    $roleQueries = array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles')
    );

    expect($roleQueries)->toHaveCount(1);

    $roleQuery = reset($roleQueries);
    $bindings = $roleQuery ? $roleQuery['bindings'] : [];

    expect($bindings)->toContain($user->id)
        ->and($bindings)->toContain($peer->id);
});

test('an inactive recipient does not trigger a role query', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->disabled()->create();
    $recipient->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::User->value, 'web'));

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        actingAs($sender)
            ->postJson(route('chat.messages.store'), [
                'recipient_id' => $recipient->id,
                'body' => 'Hello',
            ])
            ->assertStatus(422);

        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    $roleQueries = array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles')
    );

    foreach ($roleQueries as $query) {
        expect($query['bindings'])->not->toContain($recipient->id);
    }
});

test('the chat page renders successfully with empty transcript when conversation identifier is absent', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->has('conversations')
            ->where('messages.data', [])
            ->where('activeConversation', null)
        );
});

test('the chat page renders successfully with empty transcript when conversation identifier is malformed', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.index', ['conversation' => 'not-a-uuid']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->has('conversations')
            ->where('messages.data', [])
            ->where('activeConversation', null)
        );
});

test('the chat page renders successfully with empty transcript when conversation identifier is missing', function (): void {
    $user = User::factory()->create();
    $missingUuid = (string) Str::uuid();

    actingAs($user)
        ->get(route('chat.index', ['conversation' => $missingUuid]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->has('conversations')
            ->where('messages.data', [])
            ->where('activeConversation', null)
        );
});

test('first recipient chat publication does not load recipient roles', function (): void {
    Storage::fake('local');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $recipient->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::User->value, 'web'));

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('chat.png', 400, 400),
            'body' => 'Queued Hello',
        ])
        ->assertAccepted();

    $upload = ImageUpload::query()->where('user_id', $sender->id)->firstOrFail();

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        app(ProcessQueuedImageUpload::class)->handle($upload);

        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    expect($upload->refresh()->status)->toBe(ImageUploadStatus::Ready);

    $roleQueries = array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles')
    );

    foreach ($roleQueries as $query) {
        expect($query['bindings'])->not->toContain($recipient->id);
    }
});

test('chat image publication fails when recipient status is disabled', function (): void {
    Storage::fake('local');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'recipient_id' => $recipient->id,
            'image' => UploadedFile::fake()->image('chat.png', 400, 400),
            'body' => 'Queued Hello',
        ])
        ->assertAccepted();

    $upload = ImageUpload::query()->where('user_id', $sender->id)->firstOrFail();

    $recipient->forceFill(['status' => UserStatus::Disabled])->save();

    app(ProcessQueuedImageUpload::class)->handle($upload);

    expect($upload->refresh()->status)->toBe(ImageUploadStatus::Failed)
        ->and($upload->error_code)->toBe(FailImageUpload::REASON_UNAUTHORIZED);
});
