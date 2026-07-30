<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Route::get('/permission-protected', fn () => response()->noContent())
        ->middleware(['web', 'permission:publish articles']);
});

it('inherits permissions through a role using the web guard and UUID pivots', function () {
    $permission = Permission::create([
        'name' => 'publish articles',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'publisher',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);

    $authorizedUser = User::factory()->create();
    $unauthorizedUser = User::factory()->create();
    $authorizedUser->assignRole($role);

    expect($authorizedUser->can('publish articles'))->toBeTrue()
        ->and($unauthorizedUser->can('publish articles'))->toBeFalse()
        ->and(Str::isUuid($authorizedUser->id))->toBeTrue()
        ->and(DB::table('model_has_roles')->value('model_uuid'))->toBe($authorizedUser->id);
});

it('enforces the permission middleware alias', function () {
    $permission = Permission::create([
        'name' => 'publish articles',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'publisher',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);

    $authorizedUser = User::factory()->create();
    $authorizedUser->assignRole($role);

    actingAs($authorizedUser);
    get('/permission-protected')->assertSuccessful();

    actingAs(User::factory()->create());
    get('/permission-protected')->assertForbidden();
});
