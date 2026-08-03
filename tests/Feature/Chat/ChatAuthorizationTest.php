<?php

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Events\Chat\ChatMessageSent;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function (): void {
    Event::fake([ChatMessageSent::class]);
    Queue::fake();
});

/**
 * Point the application at a Pusher-protocol connection and re-register the
 * channels on it.
 *
 * `Broadcast::channel()` resolves the default driver when it is called, so the
 * channels registered while booting belong to the `null` broadcaster used in
 * tests. Without re-registering, a switched default would answer 403 for every
 * user and the authorization assertions would pass for the wrong reason.
 */
function useChatChannels(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);

    Broadcast::purge('reverb');

    require base_path('routes/channels.php');
}

test('a non participant cannot read a conversation', function (): void {
    $outsider = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        actingAs($outsider)
            ->getJson(route('chat.conversations.show', $conversation))
            ->assertForbidden();

        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    $roleQueries = array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles')
    );

    foreach ($roleQueries as $query) {
        expect($query['bindings'])->not->toContain($first->id)
            ->and($query['bindings'])->not->toContain($second->id);
    }

    actingAs($outsider)
        ->postJson(route('chat.conversations.read', $conversation), [
            'message_id' => Message::query()->value('id'),
        ])
        ->assertForbidden();
});

test('a non participant cannot send into a conversation', function (): void {
    $outsider = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();

    actingAs($outsider)
        ->postJson(route('chat.messages.store'), [
            'conversation_id' => $conversation->id,
            'body' => 'Not mine to send',
        ])
        ->assertForbidden();

    expect(Message::query()->count())->toBe(0);
});

test('only participants can open a private chat image', function (): void {
    Storage::fake('local');

    $first = User::factory()->create();
    $second = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = Conversation::factory()->between($first, $second)->create();
    $message = Message::factory()->withImage()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
    ]);
    Storage::disk('local')->put((string) $message->image_path, 'image');

    $response = actingAs($second)
        ->get(route('chat.messages.image', $message))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))
        ->toContain('private')
        ->toContain('no-store');

    actingAs($outsider)->get(route('chat.messages.image', $message))->assertForbidden();
});

test('a sender cannot react to their own message and invalid emoji is rejected', function (): void {
    $sender = User::factory()->create();
    $peer = User::factory()->create();
    $conversation = Conversation::factory()->between($sender, $peer)->create();
    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
    ]);

    actingAs($sender)
        ->putJson(route('chat.messages.reaction.update', $message), ['emoji' => '👍'])
        ->assertForbidden();

    actingAs($peer)
        ->putJson(route('chat.messages.reaction.update', $message), ['emoji' => 'not-allowed'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('emoji');
});

test('a non participant is refused the conversation channel', function (): void {
    useChatChannels();

    $outsider = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();
    $channel = 'private-chat.conversation.'.$conversation->id;

    actingAs($first)
        ->post('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '1234.5678'])
        ->assertOk();

    actingAs($outsider)
        ->post('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '1234.5678'])
        ->assertForbidden();
});

test('a Super Admin is refused the conversation channel and uses the audit surface', function (): void {
    useChatChannels();

    $superAdmin = userWithRole(Role::SuperAdmin);

    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();

    actingAs($superAdmin)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-chat.conversation.'.$conversation->id,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();

    actingAs($superAdmin)
        ->getJson(route('chat.conversations.show', $conversation))
        ->assertForbidden();
});

test('only an Active user is granted the chat control channel', function (): void {
    useChatChannels();

    $active = User::factory()->create();
    $disabled = User::factory()->disabled()->create();

    actingAs($active)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.control', 'socket_id' => '1234.5678'])
        ->assertOk();

    /*
     * A non-Active account never reaches the channel callback: access
     * enforcement signs it out first. The callback's own Active check stays as
     * the second line of defence for a status that changes mid-session.
     */
    actingAs($disabled)
        ->post('/broadcasting/auth', ['channel_name' => 'private-chat.control', 'socket_id' => '1234.5678'])
        ->assertRedirect(route('login'));
});

test('a non Active peer can no longer receive new messages', function (): void {
    $sender = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($sender, $peer)->create();

    $peer->forceFill(['status' => UserStatus::Disabled])->save();

    actingAs($sender)
        ->postJson(route('chat.messages.store'), [
            'conversation_id' => $conversation->id,
            'body' => 'Still there?',
        ])
        ->assertForbidden();

    expect(Message::query()->count())->toBe(0);
});

test('history stays readable when the peer is no longer Active', function (): void {
    $user = User::factory()->create();
    $peer = User::factory()->create();

    $conversation = Conversation::factory()->between($user, $peer)->create();
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $peer->id,
        'body' => 'Earlier note',
    ]);

    $peer->forceFill(['status' => UserStatus::Disabled])->save();

    $response = actingAs($user)
        ->getJson(route('chat.conversations.show', $conversation))
        ->assertOk();

    expect($response->json('messages.0.body'))->toBe('Earlier note')
        ->and($response->json('conversation.participant.available'))->toBeFalse();
});

test('every user chat surface is closed while chat is switched off', function (): void {
    app(ChatSettings::class)->chatEnabled = false;

    $user = User::factory()->create();
    $peer = User::factory()->create();
    $conversation = Conversation::factory()->between($user, $peer)->create();

    actingAs($user)->get(route('chat.index'))->assertForbidden();
    actingAs($user)->getJson(route('chat.conversations.index'))->assertForbidden();
    actingAs($user)->getJson(route('chat.conversations.show', $conversation))->assertForbidden();
    actingAs($user)->getJson(route('chat.recipients.index', ['search' => 'ab']))->assertForbidden();
    actingAs($user)
        ->postJson(route('chat.messages.store'), ['recipient_id' => $peer->id, 'body' => 'Hi'])
        ->assertForbidden();
});

test('a guest cannot post a message', function (): void {
    post(route('chat.messages.store'))->assertRedirect(route('login'));
});
