<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\artisan;

it('creates and maps the authorization catalog idempotently', function () {
    pendingCommand(artisan('auth:sync-authorization'))->assertSuccessful();
    pendingCommand(artisan('auth:sync-authorization'))->assertSuccessful();

    expect(Role::query()->where('guard_name', 'web')->pluck('name')->sort()->values()->all())
        ->toBe(collect(app(AuthorizationCatalog::class)->roles())->pluck('value')->sort()->values()->all())
        ->and(Permission::query()->where('guard_name', 'web')->count())
        ->toBe(count(PermissionEnum::cases()))
        ->and(Role::findByName(RoleEnum::Admin->value)->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(app(AuthorizationCatalog::class)->permissionsFor(RoleEnum::Admin))->pluck('value')->sort()->values()->all());
});

it('reports dry-run changes without mutating records', function () {
    Role::findOrCreate('obsolete', 'web');
    Permission::findOrCreate('obsolete permission', 'web');

    pendingCommand(artisan('auth:sync-authorization', ['--dry-run' => true]))
        ->expectsOutputToContain('Dry run')
        ->expectsOutputToContain('obsolete')
        ->assertSuccessful();

    expect(Role::findByName('obsolete', 'web'))->not->toBeNull()
        ->and(Permission::findByName('obsolete permission', 'web'))->not->toBeNull();
});

it('prunes web catalog drift and preserves other guards', function () {
    Role::findOrCreate('obsolete', 'web');
    Permission::findOrCreate('obsolete permission', 'web');
    Role::findOrCreate('api-role', 'api');
    Permission::findOrCreate('api-permission', 'api');

    pendingCommand(artisan('auth:sync-authorization'))->assertSuccessful();

    expect(Role::query()->where('name', 'obsolete')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'obsolete permission')->exists())->toBeFalse()
        ->and(Role::query()->where('name', 'api-role')->where('guard_name', 'api')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'api-permission')->where('guard_name', 'api')->exists())->toBeTrue();
});
