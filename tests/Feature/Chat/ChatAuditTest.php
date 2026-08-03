<?php

use App\Enums\Role;
use App\Http\Controllers\Chat\AuditController;
use App\Models\Chat\AuditLog;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\Chat\Participant;
use App\Models\Chat\Reaction;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * Create a Super Admin able to reach the audit surface.
 */
function chatAuditor(): User
{
    return userWithRole(Role::SuperAdmin);
}

test('a Super Admin can list every conversation', function (): void {
    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();
    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
    ]);

    actingAs($auditor)
        ->get(route('chat.audit.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/audit/Index')
            ->has('conversations.data', 1)
            ->where('conversations.data.0.message_count', 3)
            ->has('conversations.data.0.participants', 2),
        );
});

test('the audit list reads every participant role in one query', function (): void {
    $auditor = chatAuditor();

    $openConversation = fn (): Conversation => Conversation::factory()
        ->between(userWithRole(Role::User), userWithRole(Role::User))
        ->create();

    $openConversation();

    $response = null;
    $list = function () use ($auditor, &$response): void {
        $response = actingAs($auditor)->get(route('chat.audit.index'))->assertOk();
    };

    /* Warms the auditor's own role lookup, which the audit policy reads. */
    $list();

    $single = roleQueryCount($list);

    expect($single)->toBe(1);

    $response->assertInertia(fn (Assert $page) => $page
        ->where('conversations.data.0.participants.0.role', Role::User->label()),
    );

    $openConversation();
    $openConversation();

    /* Six participants cost what two did: the audit list never walks them. */
    expect(roleQueryCount($list))->toBe($single);

    $response->assertInertia(fn (Assert $page) => $page->has('conversations.data', 3));
});

test('opening a conversation records permanent access metadata', function (): void {
    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
        'body' => 'Audited content',
    ]);

    actingAs($auditor)
        ->get(route('chat.audit.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/audit/Show')
            ->has('messages.data', 1)
            ->where('messages.data.0.body', 'Audited content'),
        );

    $log = AuditLog::query()->firstOrFail();

    expect($log->conversation_id)->toBe($conversation->id)
        ->and($log->viewer_id)->toBe($auditor->id)
        ->and($log->viewed_at)->not->toBeNull();

    /* Metadata only: no copy of any message body is stored. */
    expect(array_keys($log->getAttributes()))->not->toContain('body');

    /* A second opening is a second permanent record, never an update. */
    actingAs($auditor)->get(route('chat.audit.show', $conversation))->assertOk();

    expect(AuditLog::query()->count())->toBe(2);
});

test('an auditor can view an image and its current reaction read only', function (): void {
    Storage::fake('local');

    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $conversation = Conversation::factory()->between($first, $second)->create();
    $message = Message::factory()->withImage()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
    ]);
    $reaction = (new Reaction)->forceFill([
        'user_id' => $second->id,
        'emoji' => '❤️',
    ]);
    $message->reactions()->save($reaction);
    Storage::disk('local')->put((string) $message->image_path, 'image');

    actingAs($auditor)
        ->get(route('chat.audit.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('messages.data.0.image.url', route('chat.audit.messages.image', $message))
            ->where('messages.data.0.reactions.0.emoji', '❤️'),
        );

    $response = actingAs($auditor)
        ->get(route('chat.audit.messages.image', $message))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Content-Disposition', 'inline')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))
        ->toContain('private')
        ->toContain('no-store')
        ->and($response->streamedContent())->toBe('image')
        ->and(AuditLog::query()->count())->toBe(2);
});

test('an audited image attempt is recorded even when the file is gone', function (): void {
    Storage::fake('local');

    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $conversation = Conversation::factory()->between($first, $second)->create();

    /* The row survives, the file does not. */
    $message = Message::factory()->withImage()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
    ]);

    actingAs($auditor)
        ->get(route('chat.audit.messages.image', $message))
        ->assertNotFound();

    /* Access is permanent: the auditor asked, so the attempt is on the record. */
    expect(AuditLog::query()->count())->toBe(1);
});

test('an audit never touches the participants receipts or notifications', function (): void {
    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $first->id,
    ]);

    $before = Participant::query()
        ->where('conversation_id', $conversation->id)
        ->pluck('last_read_at', 'user_id');

    actingAs($auditor)->get(route('chat.audit.show', $conversation))->assertOk();

    $after = Participant::query()
        ->where('conversation_id', $conversation->id)
        ->pluck('last_read_at', 'user_id');

    expect($after->toArray())->toBe($before->toArray())
        ->and($second->fresh()->notifications()->count())->toBe(0);
});

test('a Super Admin can find a conversation by participant name', function (): void {
    $auditor = chatAuditor();

    $wanted = User::factory()->create(['name' => 'Amelia Stone']);
    Conversation::factory()->between($wanted, User::factory()->create())->create();
    Conversation::factory()
        ->between(User::factory()->create(['name' => 'Bruno Vega']), User::factory()->create(['name' => 'Cora Diaz']))
        ->create();

    actingAs($auditor)
        ->get(route('chat.audit.index', ['search' => 'Amelia']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('search', 'Amelia')
            ->has('conversations.data', 1)
            ->where('conversations.data.0.participants.0.name', $wanted->name),
        );

    /* Message bodies stay outside the searchable surface. */
    actingAs($auditor)
        ->get(route('chat.audit.index', ['search' => 'Zeno']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('conversations.data', 0));
});

test('the audit search does not open the surface to anyone else', function (): void {
    $user = User::factory()->create();

    actingAs($user)->get(route('chat.audit.index', ['search' => 'Amelia']))->assertForbidden();
});

test('a Super Admin can page past the first window of a long transcript', function (): void {
    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();

    /* Distinct timestamps so the oldest-first order is unambiguous. */
    foreach (range(1, 220) as $index) {
        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $first->id,
            'body' => 'Message '.$index,
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $firstPage = actingAs($auditor)
        ->get(route('chat.audit.show', $conversation))
        ->assertOk();

    $window = $firstPage->viewData('page')['props']['messages']['data'];

    expect($window[0]['body'])->toBe('Message 1')
        ->and($firstPage->viewData('page')['props']['conversation']['message_count'])->toBe(220);

    /* The message that used to fall outside the fixed 200-row window. */
    $lastPage = actingAs($auditor)
        ->get(route('chat.audit.show', [
            'conversation' => $conversation->id,
            AuditController::MESSAGES_PAGE => 5,
        ]))
        ->assertOk();

    $bodies = collect(inertiaRows($lastPage->viewData('page')['props']['messages']['data']))->pluck('body');

    expect($bodies)->toContain('Message 201')
        ->toContain('Message 220');
});

test('the access log is paged rather than truncated', function (): void {
    $auditor = chatAuditor();
    $conversation = Conversation::factory()
        ->between(User::factory()->create(), User::factory()->create())
        ->create();

    AuditLog::factory()->count(25)->create([
        'conversation_id' => $conversation->id,
        'viewer_id' => $auditor->id,
    ]);

    $response = actingAs($auditor)
        ->get(route('chat.audit.show', [
            'conversation' => $conversation->id,
            AuditController::LOGS_PAGE => 2,
        ]))
        ->assertOk();

    /* 25 existing records plus the one this very request wrote. */
    expect($response->viewData('page')['props']['auditLogs']['data'])->not->toBeEmpty();
    expect(AuditLog::query()->count())->toBe(26);
});

test('a user without the Super Admin role cannot audit', function (): void {
    $user = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();

    actingAs($user)->get(route('chat.audit.index'))->assertForbidden();
    actingAs($user)->get(route('chat.audit.show', $conversation))->assertForbidden();
});

test('the audit surface stays open while chat is switched off', function (): void {
    app(ChatSettings::class)->chatEnabled = false;

    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();

    $conversation = Conversation::factory()->between($first, $second)->create();

    actingAs($auditor)->get(route('chat.audit.index'))->assertOk();
    actingAs($auditor)->get(route('chat.audit.show', $conversation))->assertOk();

    expect(AuditLog::query()->count())->toBe(1);
});

test('the audit list presents a multi-role participant using enum priority', function (): void {
    $auditor = chatAuditor();
    $first = User::factory()->create();
    $second = User::factory()->create();

    // Assign User first, then SuperAdmin to verify priority doesn't depend on assignment order
    $first->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::User->value, 'web'));
    $first->assignRole(Spatie\Permission\Models\Role::findOrCreate(Role::SuperAdmin->value, 'web'));

    Conversation::factory()->between($first, $second)->create();

    actingAs($auditor)
        ->get(route('chat.audit.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('conversations.data.0.participants', 2)
            ->where('conversations.data.0.participants', function (Collection $participants) use ($first): bool {
                $p = $participants->firstWhere('id', $first->id);

                return $p && $p['role'] === Role::SuperAdmin->label();
            })
        );
});
