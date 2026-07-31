<?php

use App\Enums\PasswordPolicy;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Settings\AuthenticationSettings;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

/*
 * The strict preset performs a compromised-password lookup, so the verifier is
 * always faked. Tests that do not exercise passwords run against the basic
 * preset; password and authentication tests opt back into the real policy with
 * usePasswordPolicy().
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        app()->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            public function verify($data): bool
            {
                return true;
            }
        });

        usePasswordPolicy(PasswordPolicy::Basic);
    })
    ->in('Feature');

pest()->printer()->compact();

pest()->tia()
    ->filtered()
    ->baselined();

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Run the current test against a specific password policy preset.
 */
function usePasswordPolicy(PasswordPolicy $policy): void
{
    app(AuthenticationSettings::class)->passwordPolicy = $policy;
}

/**
 * Create a user holding the given application role.
 *
 * The authorization catalog is synchronized by an Artisan command rather than a
 * seeder, so the role has to be created for the `web` guard on demand here.
 */
function userWithRole(Role $role): User
{
    $user = User::factory()->create();

    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate($role->value, 'web'));

    return $user;
}

/**
 * Create a user allowed to manage the application settings.
 */
function settingsManager(): User
{
    $user = User::factory()->create();

    $user->givePermissionTo(
        Spatie\Permission\Models\Permission::findOrCreate(Permission::ManageSettings->value, 'web'),
    );

    return $user;
}
