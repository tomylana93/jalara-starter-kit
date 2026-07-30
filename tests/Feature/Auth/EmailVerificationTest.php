<?php

use App\Jobs\SendEmailVerification;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the email verification notice for unverified users', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk();
});

it('queues another email verification notification', function () {
    Queue::fake();
    $user = User::factory()->unverified()->create();

    actingAs($user);

    post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Queue::assertPushed(
        SendEmailVerification::class,
        fn (SendEmailVerification $job): bool => $job->user->is($user),
    );
});

it('sends the queued email verification notification', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    new SendEmailVerification($user)->handle();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('verifies an email using a valid signed URL', function () {
    $user = User::factory()->unverified()->create();
    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    actingAs($user);

    get($verificationUrl)
        ->assertRedirect(route('dashboard', ['verified' => 1]));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('redirects unverified users away from verified routes', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirectToRoute('verification.notice');
});
