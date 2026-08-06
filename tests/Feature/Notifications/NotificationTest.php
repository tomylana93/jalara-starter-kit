<?php

use App\Models\User;
use App\Notifications\RealtimeTestNotification;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelBack;

/**
 * Send the reference notification without broadcasting over a real connection.
 */
function sendNotification(User $user, string $title = 'Title', string $message = 'Message', ?string $url = '/dashboard'): void
{
    Event::fake([BroadcastNotificationCreated::class]);

    $user->notify(new RealtimeTestNotification($title, $message, $url));
}

/**
 * Point the application at a Pusher-protocol connection and re-register the
 * channels on it.
 *
 * `Broadcast::channel()` resolves the default driver when it is called, so the
 * channels registered while booting belong to the `null` broadcaster used in
 * tests. Without re-registering, a switched default would answer 403 for every
 * user and the authorization assertions would pass for the wrong reason.
 */
function useReverbChannels(): void
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

it('stores the standard payload on the database channel', function () {
    $user = User::factory()->create();

    sendNotification($user, 'Deploy finished', 'The release is live.', '/dashboard');

    $notification = $user->notifications()->sole();

    expect($notification->data)->toBe([
        'type' => 'test',
        'title' => 'Deploy finished',
        'message' => 'The release is live.',
        'url' => '/dashboard',
    ])
        ->and($notification->read_at)->toBeNull();
});

it('keeps a notification without a url safe to store', function () {
    $user = User::factory()->create();

    sendNotification($user, 'No destination', 'Nothing to open.', null);

    expect($user->notifications()->sole()->data['url'])->toBeNull();
});

it('broadcasts on the private user channel with the same payload contract', function () {
    Event::fake([BroadcastNotificationCreated::class]);

    $user = User::factory()->create();
    $user->notify(new RealtimeTestNotification('Realtime', 'Sent over the socket.', '/dashboard'));

    $id = $user->notifications()->sole()->id;

    Event::assertDispatched(
        BroadcastNotificationCreated::class,
        function (BroadcastNotificationCreated $event) use ($user, $id): bool {
            $channels = array_map(strval(...), $event->broadcastOn());
            $payload = $event->broadcastWith();

            return $channels === ['private-App.Models.User.'.$user->id]
                && $payload['id'] === $id
                && $payload['type'] === 'test'
                && $payload['title'] === 'Realtime'
                && $payload['message'] === 'Sent over the socket.'
                && $payload['url'] === '/dashboard'
                && $payload['read_at'] === null
                && is_string($payload['created_at']);
        },
    );
});

it('shares the five newest notifications and the unread count with the bell', function () {
    $user = User::factory()->create();

    /* Distinct timestamps, so "newest first" is an observable ordering. */
    foreach (range(1, 4) as $index) {
        travel(1)->seconds();
        sendNotification($user, "Title {$index}", "Message {$index}");
    }

    /* Send three more notifications in the same second to test id desc tie-breaker. */
    travel(1)->seconds();
    sendNotification($user, 'Title 5', 'Message 5');
    sendNotification($user, 'Title 6', 'Message 6');
    sendNotification($user, 'Title 7', 'Message 7');

    travelBack();

    $sortedTied = $user->notifications()
        ->get()
        ->filter(fn ($notification) => in_array($notification->data['title'] ?? null, ['Title 5', 'Title 6', 'Title 7'], true))
        ->sortByDesc('id')
        ->values();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notificationBell.items', 5)
            ->where('notificationBell.unreadCount', 7)
            ->where('notificationBell.items.0.id', $sortedTied[0]->id)
            ->where('notificationBell.items.1.id', $sortedTied[1]->id)
            ->where('notificationBell.items.2.id', $sortedTied[2]->id)
            ->where('notificationBell.items.3.title', 'Title 4')
            ->where('notificationBell.items.4.title', 'Title 3')
        );
});

it('shares an empty bell state with guests without querying', function () {
    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('notificationBell.items', [])
                ->where('notificationBell.unreadCount', 0)
            );

        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    $notificationQueries = array_filter(
        $queries,
        fn (array $query): bool => str_contains((string) $query['query'], 'notifications')
    );

    expect($notificationQueries)->toBeEmpty();
});

it('paginates the notification page ten at a time, newest first', function () {
    $user = User::factory()->create();

    foreach (range(1, 12) as $index) {
        travel(1)->seconds();
        sendNotification($user, "Title {$index}");
    }

    travelBack();

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/Index')
            ->has('notifications.data', 10)
            ->where('notifications.meta.perPage', 10)
            ->where('notifications.meta.page', 1)
            ->where('notifications.meta.lastPage', 2)
            ->where('notifications.meta.total', 12)
            ->where('notifications.meta.from', 1)
            ->where('notifications.meta.to', 10)
            ->where('notifications.data.0.title', 'Title 12')
            ->where('filter', 'all')
        );

    actingAs($user)
        ->get(route('notifications.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 2)
            ->where('notifications.meta.page', 2)
        );

    actingAs($user)
        ->get(route('notifications.index', ['page' => 5]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 2)
            ->where('notifications.meta.page', 2)
            ->where('notifications.meta.lastPage', 2)
            ->where('notifications.meta.from', 11)
            ->where('notifications.meta.to', 12)
        );
});

it('filters the page down to unread notifications', function () {
    $user = User::factory()->create();

    sendNotification($user, 'Read one');
    travel(1)->seconds();
    sendNotification($user, 'Unread one');
    travelBack();

    /* Oldest first puts "Read one" at the front. */
    $user->notifications()->reorder('created_at')->first()?->markAsRead();

    actingAs($user)
        ->get(route('notifications.index', ['filter' => 'unread']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.title', 'Unread one')
            ->where('filter', 'unread')
        );
});

it('rejects an unknown filter value', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('notifications.index', ['filter' => 'archived']))
        ->assertSessionHasErrors('filter');
});

it('marks a single notification as read', function () {
    $user = User::factory()->create();
    sendNotification($user);
    $notification = $user->notifications()->sole();

    actingAs($user)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('carries the read and the navigation in a single request', function () {
    $user = User::factory()->create();
    sendNotification($user);
    $notification = $user->notifications()->sole();

    actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read', $notification->id), ['open' => true])
        ->assertRedirect('/dashboard');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('returns to the previous page when the read carries no open intent', function () {
    $user = User::factory()->create();
    sendNotification($user);
    $notification = $user->notifications()->sole();

    actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect(route('notifications.index'));
});

it('ignores the open intent for a notification without a destination', function () {
    $user = User::factory()->create();
    sendNotification($user, url: null);
    $notification = $user->notifications()->sole();

    actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read', $notification->id), ['open' => true])
        ->assertRedirect(route('notifications.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('refuses to follow a notification destination that leaves the application', function () {
    $user = User::factory()->create();
    sendNotification($user, url: 'https://evil.example.com/steal');
    $notification = $user->notifications()->sole();

    actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read', $notification->id), ['open' => true])
        ->assertRedirect(route('notifications.index'));
});

it('rejects a non-boolean open intent', function () {
    $user = User::factory()->create();
    sendNotification($user);
    $notification = $user->notifications()->sole();

    actingAs($user)
        ->patch(route('notifications.read', $notification->id), ['open' => 'maybe'])
        ->assertSessionHasErrors('open');
});

it('marks every unread notification of the user as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    sendNotification($user, 'Mine one');
    sendNotification($user, 'Mine two');
    sendNotification($other, 'Theirs');

    actingAs($user)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
    /* Another user's unread state is untouched. */
    expect($other->unreadNotifications()->count())->toBe(1);
});

it('sends a test notification through the command', function () {
    Event::fake([BroadcastNotificationCreated::class]);

    $user = User::factory()->create();

    pendingCommand(artisan('notification:test', ['email' => $user->email]))->assertSuccessful();

    expect($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->sole()->data['type'])->toBe('test');

    Event::assertDispatched(BroadcastNotificationCreated::class);
});

it('fails the command for an unknown email without notifying anyone', function () {
    User::factory()->create();

    pendingCommand(artisan('notification:test', ['email' => 'nobody@example.com']))->assertFailed();

    expect(DB::table('notifications')->count())->toBe(0);
});

it('denies guests every notification route', function () {
    $user = User::factory()->create();
    sendNotification($user);
    $notification = $user->notifications()->sole();

    get(route('notifications.index'))->assertRedirect(route('login'));
    patch(route('notifications.read-all'))->assertRedirect(route('login'));
    patch(route('notifications.read', $notification->id))->assertRedirect(route('login'));
});

it('never shows one user the notifications of another', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    sendNotification($owner, 'Owner only');

    actingAs($other)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 0)
            ->where('notificationBell.unreadCount', 0)
        );
});

it('answers 404 when marking another user notification as read', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    sendNotification($owner);
    $notification = $owner->notifications()->sole();

    actingAs($other)
        ->patch(route('notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('authorizes a user onto only their own notification channel', function () {
    useReverbChannels();

    $owner = User::factory()->create();
    $other = User::factory()->create();

    actingAs($owner)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$owner->id,
            'socket_id' => '1234.5678',
        ])
        ->assertOk();

    /*
     * Guards against comparing UUIDs with an integer cast, which collapses every
     * identifier to 0 and authorizes any user onto any other user's channel.
     */
    actingAs($other)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$owner->id,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

it('denies guests the notification channel', function () {
    useReverbChannels();

    $owner = User::factory()->create();

    post('/broadcasting/auth', [
        'channel_name' => 'private-App.Models.User.'.$owner->id,
        'socket_id' => '1234.5678',
    ])->assertForbidden();
});
