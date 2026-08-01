<?php

use App\Events\Chat\ChatAvailabilityChanged;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

test('chat is available by default', function (): void {
    expect(app(ChatSettings::class)->chatEnabled)->toBeTrue()
        ->and(app(ChatSettings::class)->imageUploadsEnabled)->toBeTrue();
});

test('a settings manager can read the chat settings page', function (): void {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.chat.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Chat')
            ->where('settings.chatEnabled', true)
            ->where('settings.imageUploadsEnabled', true),
        );
});

test('the image upload toggle is stored and announced with the full chat configuration', function (): void {
    Event::fake([ChatAvailabilityChanged::class]);

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.chat.update'), [
            'chatEnabled' => '1',
            'imageUploadsEnabled' => '0',
        ])
        ->assertRedirect(route('settings.chat.edit'));

    expect(app(ChatSettings::class)->refresh()->imageUploadsEnabled)->toBeFalse();

    Event::assertDispatched(
        ChatAvailabilityChanged::class,
        fn (ChatAvailabilityChanged $event): bool => $event->enabled && ! $event->imageUploadsEnabled,
    );
});

test('the chat toggle is stored and announced to online clients', function (): void {
    Event::fake([ChatAvailabilityChanged::class]);

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.chat.update'), ['chatEnabled' => '0'])
        ->assertRedirect(route('settings.chat.edit'));

    expect(app(ChatSettings::class)->refresh()->chatEnabled)->toBeFalse();

    Event::assertDispatched(
        ChatAvailabilityChanged::class,
        fn (ChatAvailabilityChanged $event): bool => $event->enabled === false,
    );
});

test('saving the same value announces nothing', function (): void {
    Event::fake([ChatAvailabilityChanged::class]);

    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.chat.update'), ['chatEnabled' => '1'])
        ->assertRedirect(route('settings.chat.edit'));

    Event::assertNotDispatched(ChatAvailabilityChanged::class);
});

test('the chat toggle requires a boolean', function (): void {
    actingAs(settingsManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('settings.chat.update'), ['chatEnabled' => 'maybe'])
        ->assertSessionHasErrors('chatEnabled');
});

test('a user without the manage settings permission cannot reach chat settings', function (): void {
    $user = User::factory()->create();

    actingAs($user)->get(route('settings.chat.edit'))->assertForbidden();
    actingAs($user)->put(route('settings.chat.update'), ['chatEnabled' => '0'])->assertForbidden();
});

test('a guest cannot reach chat settings', function (): void {
    get(route('settings.chat.edit'))->assertRedirect(route('login'));
    put(route('settings.chat.update'))->assertRedirect(route('login'));
});
