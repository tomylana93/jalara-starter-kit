<?php

use App\Models\User;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * A valid square avatar upload.
 */
function avatarImage(): File
{
    return UploadedFile::fake()->image('avatar.png', 512, 512);
}

it('stores an avatar for the authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.profile.avatar.store'), ['image' => avatarImage()])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending');

    $user->refresh();

    expect($user->avatar_path)->toStartWith('avatars/'.$user->getKey().'/')
        ->and(Storage::disk('public')->exists($user->avatar_path))->toBeTrue();
});

it('removes the previous avatar when it is replaced', function () {
    $user = User::factory()->create();

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => avatarImage()]);
    $original = $user->refresh()->avatar_path;

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => avatarImage()]);
    $replacement = $user->refresh()->avatar_path;

    expect($replacement)->not->toBe($original)
        ->and(Storage::disk('public')->exists($original))->toBeFalse()
        ->and(Storage::disk('public')->exists($replacement))->toBeTrue();
});

it('clears the avatar and deletes the file when removed', function () {
    $user = User::factory()->create();

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => avatarImage()]);
    $path = $user->refresh()->avatar_path;

    actingAs($user)
        ->delete(route('account.profile.avatar.destroy'))
        ->assertRedirect(route('account.profile.edit'));

    expect($user->refresh()->avatar_path)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('rejects an avatar that is not square', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.profile.avatar.store'), [
            'image' => UploadedFile::fake()->image('avatar.png', 512, 256),
        ])
        ->assertSessionHasErrors('image');

    expect($user->refresh()->avatar_path)->toBeNull();
});

it('rejects a disallowed avatar file type', function () {
    actingAs(User::factory()->create())
        ->post(route('account.profile.avatar.store'), [
            'image' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');
});

it('rejects an avatar above the size limit', function () {
    actingAs(User::factory()->create())
        ->post(route('account.profile.avatar.store'), [
            'image' => avatarImage()->size(3 * 1024),
        ])
        ->assertSessionHasErrors('image');
});

it('only ever changes the authenticated user avatar', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => avatarImage()]);

    expect($user->refresh()->avatar_path)->not->toBeNull()
        ->and($other->refresh()->avatar_path)->toBeNull();
});

it('requires authentication', function () {
    post(route('account.profile.avatar.store'))->assertRedirect(route('login'));
    delete(route('account.profile.avatar.destroy'))->assertRedirect(route('login'));
});

it('shares the avatar url and never the stored path', function () {
    $user = User::factory()->create();

    actingAs($user)->post(route('account.profile.avatar.store'), ['image' => avatarImage()]);
    $path = $user->refresh()->avatar_path;

    actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.avatar', Storage::disk('public')->url($path))
            ->missing('auth.user.avatar_path'),
        );
});

it('shares a null avatar when none is stored', function () {
    actingAs(User::factory()->create())
        ->get(route('account.profile.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.avatar', null));
});
