<?php

use App\Actions\Chat\TrackChatPageContext;
use App\Events\Chat\ChatMessageSent;
use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelBack;

beforeEach(function (): void {
    Event::fake([ChatMessageSent::class]);
});

/**
 * Store one message straight into a conversation, bypassing HTTP.
 */
function chatMessageFrom(Conversation $conversation, User $sender, string $body = 'Hello'): Message
{
    return Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'body' => $body,
    ]);
}

/**
 * Run the delivery decision the queue worker would run.
 */
function deliverChatMessage(Message $message): void
{
    new DeliverChatMessageNotification($message)
        ->handle(app(ChatSettings::class), app(TrackChatPageContext::class));
}

/**
 * Report one Chat page instance as open, the way that page does.
 */
function openChatPage(User $user, string $contextId): TestResponse
{
    return actingAs($user)
        ->postJson(route('chat.context.store', ['context' => $contextId]))
        ->assertOk();
}

function closeChatPage(User $user, string $contextId): TestResponse
{
    return actingAs($user)
        ->deleteJson(route('chat.context.destroy', ['context' => $contextId]))
        ->assertOk();
}

test('a waiting message creates one notification that carries no preview', function (): void {
    $sender = User::factory()->create(['name' => 'Nadia Pratama']);
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = chatMessageFrom($conversation, $sender, 'A private secret');

    deliverChatMessage($message);

    $notification = $recipient->fresh()->notifications()->firstOrFail();

    expect($notification->data['type'])->toBe(ChatMessageNotification::TYPE);
    expect($notification->data['title'])->toBe('Nadia Pratama');
    expect($notification->data['message'])->not->toContain('A private secret');
    expect($notification->data['url'])->toContain($conversation->id);
});

test('one active notification per conversation is kept and updated', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    foreach (range(1, 3) as $index) {
        $message = chatMessageFrom($conversation, $sender, 'Message '.$index);
        deliverChatMessage($message);
    }

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(1);
});

test('no notification is created when the recipient already read the message', function (): void {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = chatMessageFrom($conversation, $sender);

    /* The recipient's client was looking at this conversation. */
    Participant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $recipient->id)
        ->update(['last_read_at' => $message->created_at]);

    deliverChatMessage($message);

    Notification::assertNothingSent();
});

test('an open Chat page silences every direct message, not just the one on screen', function (): void {
    Notification::fake();

    $recipient = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    /* Reported by the Chat page itself; nothing else may set this. */
    openChatPage($recipient, 'tab-alpha');

    foreach ([$first, $second] as $sender) {
        $conversation = Conversation::factory()->between($sender, $recipient)->create();

        deliverChatMessage(chatMessageFrom($conversation, $sender));
    }

    Notification::assertNothingSent();
});

test('leaving the Chat page lets notifications through again', function (): void {
    $recipient = User::factory()->create();
    $sender = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    openChatPage($recipient, 'tab-alpha');
    closeChatPage($recipient, 'tab-alpha')->assertJson(['open' => false]);

    deliverChatMessage(chatMessageFrom($conversation, $sender));

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(1);
});

test('closing one Chat tab keeps the other tab silent', function (): void {
    Notification::fake();

    $recipient = User::factory()->create();
    $sender = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    openChatPage($recipient, 'tab-alpha');
    openChatPage($recipient, 'tab-beta');

    /* One tab goes away; the other is still showing every conversation. */
    closeChatPage($recipient, 'tab-alpha')->assertJson(['open' => true]);

    deliverChatMessage(chatMessageFrom($conversation, $sender));

    Notification::assertNothingSent();
});

test('notifications return once the last Chat tab is closed', function (): void {
    $recipient = User::factory()->create();
    $sender = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    openChatPage($recipient, 'tab-alpha');
    openChatPage($recipient, 'tab-beta');

    closeChatPage($recipient, 'tab-alpha')->assertJson(['open' => true]);
    closeChatPage($recipient, 'tab-beta')->assertJson(['open' => false]);

    deliverChatMessage(chatMessageFrom($conversation, $sender));

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(1);
});

test('a context that is never closed expires on its own', function (): void {
    $recipient = User::factory()->create();
    $sender = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    openChatPage($recipient, 'tab-alpha');

    /* The tab died without cleaning up; the record must not outlive its TTL. */
    travel(TrackChatPageContext::TTL_SECONDS + 1)->seconds();

    deliverChatMessage(chatMessageFrom($conversation, $sender));

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(1);

    travelBack();
});

test('one users open Chat page never silences another users notifications', function (): void {
    $onChatPage = User::factory()->create();
    $elsewhere = User::factory()->create();
    $sender = User::factory()->create();

    openChatPage($onChatPage, 'tab-alpha');

    $conversation = Conversation::factory()->between($sender, $elsewhere)->create();

    deliverChatMessage(chatMessageFrom($conversation, $sender));

    expect($elsewhere->fresh()->unreadNotifications()->count())->toBe(1);
});

test('a user cannot close or refresh another users Chat context', function (): void {
    Notification::fake();

    $recipient = User::factory()->create();
    $stranger = User::factory()->create();
    $sender = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();

    openChatPage($recipient, 'tab-alpha');

    /*
     * The identifier carries no authority: it is always scoped to the caller,
     * so a stranger reusing it only touches their own record.
     */
    closeChatPage($stranger, 'tab-alpha')->assertJson(['open' => false]);

    deliverChatMessage(chatMessageFrom($conversation, $sender));

    Notification::assertNothingSent();
    expect(app(TrackChatPageContext::class)->isOpen($recipient))->toBeTrue();
    expect(app(TrackChatPageContext::class)->isOpen($stranger))->toBeFalse();
});

test('a Chat context identifier has to look like one', function (): void {
    actingAs(User::factory()->create())
        ->postJson(route('chat.context.store', ['context' => 'short']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('context');
});

test('the widget only silences the direct message it is showing', function (): void {
    $recipient = User::factory()->create();
    $shown = User::factory()->create();
    $other = User::factory()->create();

    /* The widget moves the read marker of the conversation it displays. */
    $open = Conversation::factory()->between($shown, $recipient)->create();
    $shownMessage = chatMessageFrom($open, $shown);

    Participant::query()
        ->where('conversation_id', $open->id)
        ->where('user_id', $recipient->id)
        ->update(['last_read_at' => $shownMessage->created_at]);

    new DeliverChatMessageNotification($shownMessage)
        ->handle(app(ChatSettings::class), app(TrackChatPageContext::class));

    /* A different conversation is not on screen, so it still announces itself. */
    $background = Conversation::factory()->between($other, $recipient)->create();

    new DeliverChatMessageNotification(chatMessageFrom($background, $other))
        ->handle(app(ChatSettings::class), app(TrackChatPageContext::class));

    $unread = $recipient->fresh()->unreadNotifications()->get();

    expect($unread)->toHaveCount(1);
    expect($unread->first()->data['conversation_id'])->toBe($background->id);
});

test('a guest cannot report a Chat page context', function (): void {
    post(route('chat.context.store'))->assertRedirect(route('login'));
});

test('no notification is created for a recipient who is no longer Active', function (): void {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->disabled()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = chatMessageFrom($conversation, $sender);

    deliverChatMessage($message);

    Notification::assertNothingSent();
});

test('no notification is created while chat is switched off', function (): void {
    Notification::fake();

    app(ChatSettings::class)->chatEnabled = false;

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = chatMessageFrom($conversation, $sender);

    deliverChatMessage($message);

    Notification::assertNothingSent();
});

test('reading the conversation marks its notification as read', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = chatMessageFrom($conversation, $sender);

    deliverChatMessage($message);

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(1);

    actingAs($recipient)
        ->postJson(route('chat.conversations.read', $conversation), ['message_id' => $message->id])
        ->assertOk();

    expect($recipient->fresh()->unreadNotifications()->count())->toBe(0);
});

test('chat notifications are hidden while chat is switched off', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $recipient)->create();
    $message = chatMessageFrom($conversation, $sender);

    deliverChatMessage($message);

    actingAs($recipient)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('notifications.data', 1));

    app(ChatSettings::class)->chatEnabled = false;

    actingAs($recipient)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 0)
            ->where('notificationBell.unreadCount', 0),
        );

    /* Hidden, never deleted: the row is still there for when chat returns. */
    expect($recipient->fresh()->notifications()->count())->toBe(1);
});

test('the shared chat state reports availability and the aggregate unread total', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();
    chatMessageFrom($conversation, $peer);
    chatMessageFrom($conversation, $peer);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('chat.enabled', true)
            ->where('chat.unreadCount', 2),
        );

    app(ChatSettings::class)->chatEnabled = false;

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('chat.enabled', false)
            ->where('chat.unreadCount', 0),
        );
});

test('a guest sees no chat state', function (): void {
    get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('chat.unreadCount', 0));
});
