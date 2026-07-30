<?php

use App\Actions\Authorization\SyncAuthorization;
use App\Enums\DateFormat;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exports\UsersExport;
use App\Http\Requests\MasterData\ExportUsersRequest;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Settings\UserProvisioningSettings;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\SimpleExcel\SimpleExcelReader;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    app(SyncAuthorization::class)->handle();

    $settings = app(UserProvisioningSettings::class);
    $settings->defaultPassword = 'Jalara-Def4ult!';
    $settings->save();
});

/**
 * A user allowed to browse, create, and update users.
 */
function userManager(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

/**
 * A user allowed to browse Master Data but not to mutate a user.
 */
function userViewer(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionModel::findOrCreate(Permission::ViewUsers->value, 'web'));

    return $user;
}

/**
 * @return list<string>
 */
function masterDataRouteNames(): array
{
    return ['master-data.index', 'master-data.users.index', 'master-data.users.create'];
}

it('redirects a guest to the login screen', function (string $route) {
    get(route($route))->assertRedirectToRoute('login');
})->with(masterDataRouteNames());

it('forbids a user without the view users permission', function (string $route) {
    actingAs(User::factory()->create())
        ->get(route($route))
        ->assertForbidden();
})->with(masterDataRouteNames());

it('renders the master data landing page for a manager', function () {
    actingAs(userManager())
        ->get(route('master-data.index'))
        ->assertInertia(fn (Assert $page) => $page->component('master-data/Index'));
});

it('renders the user table with its payload contract', function () {
    actingAs(userManager())
        ->get(route('master-data.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('master-data/users/Index')
            ->whereType('users.data', 'array')
            ->where('users.meta.perPage', 10)
            ->where('users.meta.page', 1)
            ->where('users.state.sort', 'createdAt')
            ->where('users.state.direction', 'desc')
            ->where('users.state.search', null)
            ->where('users.meta.perPageOptions', [10, 25, 50])
            ->where('canCreate', true)
            ->whereType('dateFormat', 'string'),
        );
});

it('sends every timestamp as a UTC ISO 8601 instant', function () {
    $manager = userManager();
    User::factory()->create(['name' => 'Timestamped Person']);

    actingAs($manager)
        ->get(route('master-data.users.index', ['search' => 'Timestamped Person']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            /* The browser owns the timezone, so no server formatting leaks out. */
            ->where('users.data.0.createdAt', fn (string $createdAt): bool => preg_match(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/',
                $createdAt,
            ) === 1),
        );
});

it('sends the configured date format for the browser to apply', function () {
    app(GeneralSettings::class)->fill(['dateFormat' => DateFormat::DayMonthYearSlashed])->save();

    actingAs(userManager())
        ->get(route('master-data.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dateFormat', DateFormat::DayMonthYearSlashed->value),
        );
});

it('exposes no delete surface for users', function () {
    expect(Route::has('master-data.users.destroy'))->toBeFalse()
        ->and(Route::has('master-data.users.show'))->toBeFalse();
});

it('searches users by name and email', function () {
    $manager = userManager();
    User::factory()->create(['name' => 'Findable Person', 'email' => 'findable@example.com']);
    User::factory()->create(['name' => 'Hidden Person', 'email' => 'hidden@example.com']);

    actingAs($manager)
        ->get(route('master-data.users.index', ['search' => 'findable']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'findable@example.com')
            ->where('users.state.search', 'findable'),
        );
});

it('sorts users by a whitelisted column', function () {
    $manager = userManager();
    User::factory()->create(['name' => 'Aaron First']);
    User::factory()->create(['name' => 'Zoe Last']);

    actingAs($manager)
        ->get(route('master-data.users.index', ['sort' => 'name', 'direction' => 'asc', 'perPage' => 50]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data.0.name', 'Aaron First')
            ->where('users.state.sort', 'name')
            ->where('users.state.direction', 'asc'),
        );
});

it('paginates with the requested page size', function () {
    $manager = userManager();
    User::factory()->count(12)->create();

    actingAs($manager)
        ->get(route('master-data.users.index', ['perPage' => 10, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 3)
            ->where('users.meta.page', 2)
            ->where('users.meta.total', 13)
            ->where('users.meta.lastPage', 2),
        );
});

it('resolves a page past the end to the last page that exists', function () {
    $manager = userManager();
    User::factory()->count(12)->create();

    actingAs($manager)
        ->get(route('master-data.users.index', ['perPage' => 10, 'page' => 999]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 3)
            ->where('users.meta.page', 2)
            ->where('users.meta.lastPage', 2)
            ->where('users.meta.total', 13)
            ->where('users.meta.from', 11)
            ->where('users.meta.to', 13),
        );
});

it('rejects an invalid table query', function (array $query) {
    actingAs(userManager())
        ->get(route('master-data.users.index', $query))
        ->assertSessionHasErrors(array_keys($query));
})->with([
    'unknown sort column' => [['sort' => 'password']],
    'unknown direction' => [['direction' => 'sideways']],
    'unsupported page size' => [['perPage' => 1000]],
]);

it('creates an active user from a name, an email, and a role', function () {
    actingAs(userManager())
        ->post(route('master-data.users.store'), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'role' => Role::User->value,
        ])
        ->assertRedirectToRoute('master-data.users.index');

    $user = User::query()->where('email', 'new@example.com')->sole();

    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->hasRole(Role::User->value))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->is_system)->toBeFalse();
});

it('rejects a submitted status when creating a user', function () {
    actingAs(userManager())
        ->post(route('master-data.users.store'), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'role' => Role::User->value,
            'status' => UserStatus::Disabled->value,
        ])
        ->assertSessionHasErrors('status');

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
});

it('refuses to assign super admin through user management', function () {
    actingAs(userManager())
        ->post(route('master-data.users.store'), [
            'name' => 'Escalated',
            'email' => 'escalated@example.com',
            'role' => Role::SuperAdmin->value,
        ])
        ->assertSessionHasErrors('role');

    expect(User::query()->where('email', 'escalated@example.com')->exists())->toBeFalse();
});

it('forbids creating a user without the create permission', function () {
    actingAs(userViewer())
        ->get(route('master-data.users.create'))
        ->assertForbidden();

    actingAs(userViewer())
        ->post(route('master-data.users.store'), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'role' => Role::User->value,
        ])
        ->assertForbidden();
});

it('updates the name, email, status, and role of a user', function () {
    $target = User::factory()->create(['name' => 'Before', 'email' => 'before@example.com']);
    $target->assignRole(Role::User->value);

    actingAs(userManager())
        ->put(route('master-data.users.update', $target), [
            'name' => 'After',
            'email' => 'after@example.com',
            'status' => UserStatus::Disabled->value,
            'role' => Role::Admin->value,
        ])
        ->assertRedirectToRoute('master-data.users.index');

    $target->refresh();

    expect($target->name)->toBe('After')
        ->and($target->email)->toBe('after@example.com')
        ->and($target->status)->toBe(UserStatus::Disabled)
        ->and($target->hasRole(Role::Admin->value))->toBeTrue()
        ->and($target->hasRole(Role::User->value))->toBeFalse()
        ->and($target->hasVerifiedEmail())->toBeTrue();
});

it('suspends a user without an expiry that would silently reactivate it', function () {
    $target = User::factory()->suspended(now()->subDay())->create();
    $target->assignRole(Role::User->value);

    actingAs(userManager())
        ->put(route('master-data.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'status' => UserStatus::Suspended->value,
            'role' => Role::User->value,
        ])
        ->assertRedirectToRoute('master-data.users.index');

    $target->refresh();

    expect($target->status)->toBe(UserStatus::Suspended)
        ->and($target->suspended_until)->toBeNull();
});

it('rejects an invalid status or role on update', function (array $payload) {
    $target = User::factory()->create();
    $target->assignRole(Role::User->value);

    actingAs(userManager())
        ->put(route('master-data.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'status' => UserStatus::Active->value,
            'role' => Role::User->value,
            ...$payload,
        ])
        ->assertSessionHasErrors(array_keys($payload));
})->with([
    'unknown status' => [['status' => 'archived']],
    'super admin role' => [['role' => Role::SuperAdmin->value]],
]);

it('keeps the system user outside master data', function () {
    $target = User::factory()->create(['is_system' => true]);

    actingAs(userManager())
        ->get(route('master-data.users.edit', $target))
        ->assertForbidden();

    actingAs(userManager())
        ->put(route('master-data.users.update', $target), [
            'name' => 'Renamed',
            'email' => 'renamed@example.com',
            'status' => UserStatus::Disabled->value,
            'role' => Role::User->value,
        ])
        ->assertForbidden();

    expect($target->refresh()->name)->not->toBe('Renamed');
});

it('keeps a super admin outside master data', function () {
    $target = User::factory()->create();
    $target->assignRole(Role::SuperAdmin->value);

    actingAs(userManager())
        ->get(route('master-data.users.edit', $target))
        ->assertForbidden();

    actingAs(userManager())
        ->put(route('master-data.users.update', $target), [
            'name' => 'Renamed',
            'email' => 'renamed@example.com',
            'status' => UserStatus::Disabled->value,
            'role' => Role::User->value,
        ])
        ->assertForbidden();

    expect($target->refresh()->name)->not->toBe('Renamed');
});

it('never offers an edit action for a protected user', function () {
    $manager = userManager();
    User::factory()->create(['is_system' => true, 'name' => 'System Account']);

    actingAs($manager)
        ->get(route('master-data.users.index', ['search' => 'System Account']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.canUpdate', false),
        );
});

it('renders the edit screen with the current role and status', function () {
    $target = User::factory()->create();
    $target->assignRole(Role::User->value);

    actingAs(userManager())
        ->get(route('master-data.users.edit', $target))
        ->assertInertia(fn (Assert $page) => $page
            ->component('master-data/users/Edit')
            ->where('user.role', Role::User->value)
            ->where('user.status', UserStatus::Active->value)
            ->has('roleOptions', 2)
            ->has('statusOptions', count(UserStatus::cases())),
        );
});

it('offers only the assignable roles on the create screen', function () {
    actingAs(userManager())
        ->get(route('master-data.users.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('master-data/users/Create')
            ->has('roleOptions', 2)
            ->where('roleOptions.0.value', Role::Admin->value)
            ->where('roleOptions.1.value', Role::User->value)
            ->missing('statusOptions'),
        );
});

it('shares whether the user may browse master data', function () {
    actingAs(userManager())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('can.viewUsers', true));

    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('can.viewUsers', false));
});

it('offers the status and role catalogs as filter options', function () {
    actingAs(userManager())
        ->get(route('master-data.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('filterOptions.status', count(UserStatus::cases()))
            ->has('filterOptions.role', count(Role::cases()))
            ->where('filterOptions.status.0.value', UserStatus::Active->value),
        );
});

it('filters users by status', function () {
    $manager = userManager();
    User::factory()->create(['status' => UserStatus::Disabled]);
    User::factory()->create(['status' => UserStatus::Suspended]);

    actingAs($manager)
        ->get(route('master-data.users.index', ['status' => [UserStatus::Disabled->value]]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.status.value', UserStatus::Disabled->value)
            ->where('users.state.filters.status', [UserStatus::Disabled->value]),
        );
});

it('treats several values of one filter as alternatives', function () {
    $manager = userManager();
    User::factory()->create(['status' => UserStatus::Disabled]);
    User::factory()->create(['status' => UserStatus::Suspended]);

    actingAs($manager)
        ->get(route('master-data.users.index', [
            'status' => [UserStatus::Disabled->value, UserStatus::Suspended->value],
        ]))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
});

it('filters users by role', function () {
    $manager = userManager();
    $target = User::factory()->create();
    $target->assignRole(Role::User->value);

    actingAs($manager)
        ->get(route('master-data.users.index', ['role' => [Role::User->value]]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $target->id),
        );
});

it('narrows the table when the status and role filters combine', function () {
    $manager = userManager();
    $match = User::factory()->create(['status' => UserStatus::Disabled]);
    $match->assignRole(Role::User->value);

    $wrongStatus = User::factory()->create(['status' => UserStatus::Active]);
    $wrongStatus->assignRole(Role::User->value);

    actingAs($manager)
        ->get(route('master-data.users.index', [
            'status' => [UserStatus::Disabled->value],
            'role' => [Role::User->value],
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $match->id),
        );
});

it('rejects a filter value outside the allowed catalog', function (array $query) {
    actingAs(userManager())
        ->get(route('master-data.users.index', $query))
        ->assertSessionHasErrors();
})->with([
    'unknown status' => [['status' => ['exploded']]],
    'unknown role' => [['role' => ['root']]],
    'duplicated status' => [['status' => ['active', 'active']]],
    'status is not a list' => [['status' => 'active']],
]);

it('downloads the selected users as a spreadsheet', function () {
    $manager = userManager();
    $first = User::factory()->create(['name' => 'Ada Lovelace']);
    $second = User::factory()->create(['name' => 'Grace Hopper']);

    actingAs($manager)
        ->get(route('master-data.users.export', ['ids' => [$second->id, $first->id]]))
        ->assertOk()
        ->assertDownload('users.xlsx');
});

it('writes the exported rows in the order they were selected', function () {
    $manager = userManager();
    $first = User::factory()->create(['name' => 'Ada Lovelace']);
    $second = User::factory()->create(['name' => 'Grace Hopper']);

    $path = app(UsersExport::class)->write([$second->id, $first->id]);
    $rows = SimpleExcelReader::create($path)->getRows()->all();

    expect(array_column($rows, __('master_data.user.label.name')))
        ->toBe(['Grace Hopper', 'Ada Lovelace']);

    unlink($path);
});

it('exports no credential material', function () {
    $manager = userManager();

    $path = app(UsersExport::class)->write([$manager->id]);
    $contents = SimpleExcelReader::create($path)->getRows()->all();

    expect(array_keys($contents[0]))->toBe([
        __('master_data.user.label.id'),
        __('master_data.user.label.name'),
        __('master_data.user.label.email'),
        __('master_data.user.label.role'),
        __('master_data.user.label.status'),
        __('master_data.user.label.created_at'),
    ]);

    unlink($path);
});

it('rejects an export selection that cannot have come from one page', function (array $query) {
    actingAs(userManager())
        ->get(route('master-data.users.export', $query))
        ->assertSessionHasErrors();
})->with([
    'no selection' => [[]],
    'empty selection' => [['ids' => []]],
    'malformed id' => [['ids' => ['not-a-uuid']]],
    'unknown id' => [['ids' => ['0199a1f0-0000-7000-8000-000000000000']]],
]);

it('rejects a duplicated export selection', function () {
    $manager = userManager();

    actingAs($manager)
        ->get(route('master-data.users.export', ['ids' => [$manager->id, $manager->id]]))
        ->assertSessionHasErrors();
});

it('rejects an export selection larger than a page of rows', function () {
    $manager = userManager();
    $ids = User::factory()->count(ExportUsersRequest::MAX_IDS)->create()->pluck('id')->all();

    actingAs($manager)
        ->get(route('master-data.users.export', ['ids' => [...$ids, $manager->id]]))
        ->assertSessionHasErrors('ids');
});

it('protects the export behind the view users permission', function () {
    $manager = userManager();

    get(route('master-data.users.export', ['ids' => [$manager->id]]))
        ->assertRedirectToRoute('login');

    actingAs(User::factory()->create())
        ->get(route('master-data.users.export', ['ids' => [$manager->id]]))
        ->assertForbidden();
});
