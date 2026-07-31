<?php

use App\Actions\Fortify\AuthenticateUser;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the login screen', function () {
    $response = get(route('login'));

    $response->assertOk();
});

it('authenticates users using the login screen', function () {
    $user = User::factory()->create();

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    expect($user->refresh()->last_login_at)->not->toBeNull()
        ->and($user->failed_login_attempts)->toBe(0)
        ->and($user->suspended_until)->toBeNull();
});

it('rehashes passwords during login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password', ['rounds' => 4]),
    ]);
    $oldHash = $user->password;

    config(['hashing.bcrypt.rounds' => 12]);
    Hash::forgetDrivers();

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect($user->refresh()->password)->not->toBe($oldHash)
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('redirects authenticated users to their intended route', function () {
    $user = User::factory()->create();

    get(route('account.profile.edit'))
        ->assertRedirectToRoute('login');

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirectToRoute('account.profile.edit');
});

it('does not authenticate users with an invalid password', function () {
    $user = User::factory()->create();

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    assertGuest();

    expect($user->refresh()->failed_login_attempts)->toBe(0)
        ->and($user->status)->toBe(UserStatus::Active);
});

it('uses a generic message for unknown accounts and invalid passwords', function (array $credentials) {
    if ($credentials['email'] !== 'missing@example.com') {
        User::factory()->create(['email' => $credentials['email']]);
    }

    post(route('login.store'), $credentials)
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
})->with([
    'unknown account' => [[
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ]],
    'invalid password' => [[
        'email' => 'known@example.com',
        'password' => 'wrong-password',
    ]],
]);

it('throttles an email and IP without suspending the account', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();

    expect($user->refresh()->failed_login_attempts)->toBe(0)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->suspended_until)->toBeNull();

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();
});

it('reveals a disabled status only after the password is validated', function () {
    $user = User::factory()->disabled()->create();

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => __('auth.failed')]);

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => __('auth.login.message.disabled')]);

    assertGuest();

    expect($user->refresh()->status)->toBe(UserStatus::Disabled)
        ->and($user->failed_login_attempts)->toBe(0);
});

it('rejects an active or manual suspension with the localized status message', function (?DateTimeInterface $until) {
    $user = User::factory()->suspended($until)->create();

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => __('auth.login.message.suspended')]);

    assertGuest();
})->with([
    'active suspension' => [fn () => now()->addMinutes(5)],
    'manual suspension' => [null],
]);

it('reactivates an expired suspension when valid credentials are provided', function () {
    $user = User::factory()->suspended(now()->subMinute())->create([
        'failed_login_attempts' => 5,
    ]);

    post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();

    expect($user->refresh()->status)->toBe(UserStatus::Active)
        ->and($user->failed_login_attempts)->toBe(0)
        ->and($user->suspended_until)->toBeNull();
});

it('memoizes authentication resolution on the request', function () {
    $user = User::factory()->create();
    $request = Request::create(route('login.store'), 'POST', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);
    $authenticate = app(AuthenticateUser::class);

    expect($authenticate->handle($request))->toBeNull()
        ->and($authenticate->handle($request))->toBeNull()
        ->and($user->refresh()->failed_login_attempts)->toBe(0);
});

it('logs out users', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = post(route('logout'));

    $response->assertRedirect(route('home'));

    assertGuest();
});

it('rate limits users', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
