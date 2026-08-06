<?php

use App\Actions\Authorization\SyncAuthorization;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Settings\UserProvisioningSettings;
use App\Support\Users\UserImportSheet;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    app(SyncAuthorization::class)->handle();

    $settings = app(UserProvisioningSettings::class);
    $settings->defaultPassword = 'Jalara-Def4ult!';
    $settings->save();
});

/**
 * A user allowed to browse and create users.
 */
function importer(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

/**
 * Write a spreadsheet and hand it back as an upload.
 *
 * @param  list<string>  $headers
 * @param  list<list<string>>  $rows
 */
function importSheet(array $rows, array $headers = UserImportSheet::COLUMNS): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'import-test').'.xlsx';

    $writer = SimpleExcelWriter::create(file: $path, type: 'xlsx');
    $writer->addHeader($headers);

    foreach ($rows as $row) {
        $writer->addRow($row);
    }

    $writer->close();

    return new UploadedFile($path, 'users.xlsx', null, null, true);
}

it('creates every user the sheet describes', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([
                ['Ada Lovelace', 'ada@example.com', Role::Admin->value],
                ['Grace Hopper', 'grace@example.com', Role::User->value],
            ]),
        ])
        ->assertRedirect(route('master-data.users.index'))
        ->assertSessionHasNoErrors();

    $ada = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($ada->name)->toBe('Ada Lovelace')
        ->and($ada->hasRole(Role::Admin->value))->toBeTrue()
        /* Provisioned accounts start on the shared default password. */
        ->and($ada->must_change_password)->toBeTrue()
        ->and(User::query()->where('email', 'grace@example.com')->exists())->toBeTrue();
});

it('matches the header row however it was capitalized and ignores unknown columns', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet(
                [['Ada Lovelace', 'ada@example.com', Role::Admin->value, 'ignored']],
                ['Name', ' EMAIL ', 'Role', 'notes'],
            ),
        ])
        ->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('refuses an actor who may browse but not create users', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(PermissionModel::findOrCreate(Permission::ViewUsers->value, 'web'));

    actingAs($viewer)
        ->post(route('master-data.users.import'), ['sheet' => importSheet([
            ['Ada Lovelace', 'ada@example.com', Role::Admin->value],
        ])])
        ->assertForbidden();

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('rejects a file that is not a spreadsheet', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => UploadedFile::fake()->createWithContent('users.xlsx', 'not a spreadsheet'),
        ])
        ->assertSessionHasErrors('sheet');
});

it('rejects a sheet whose header row is missing a required column', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([['Ada Lovelace', 'ada@example.com']], ['name', 'email']),
        ])
        ->assertSessionHasErrors('sheet');

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('rejects a sheet with no rows', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), ['sheet' => importSheet([])])
        ->assertSessionHasErrors('sheet');
});

it('rejects a sheet holding more rows than one import accepts', function () {
    $rows = [];

    for ($index = 0; $index <= UserImportSheet::MAX_ROWS; $index++) {
        $rows[] = ["User {$index}", "user{$index}@example.com", Role::User->value];
    }

    actingAs(importer())
        ->post(route('master-data.users.import'), ['sheet' => importSheet($rows)])
        ->assertSessionHasErrors('sheet');

    expect(User::query()->where('email', 'user0@example.com')->exists())->toBeFalse();
});

it('creates nothing when a single row is invalid', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([
                ['Ada Lovelace', 'ada@example.com', Role::User->value],
                ['Grace Hopper', 'not-an-email', Role::User->value],
                ['Katherine Johnson', 'katherine@example.com', Role::User->value],
            ]),
        ])
        ->assertSessionHasErrors('rows.3.email');

    /* All or nothing: the two valid rows are not written either. */
    expect(User::query()->whereIn('email', [
        'ada@example.com',
        'katherine@example.com',
    ])->count())->toBe(0);
});

it('reports the spreadsheet line rather than the position among the rows it kept', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([
                ['Ada Lovelace', 'ada@example.com', Role::User->value],
                /* A blank row is absence; it must not shift the numbering. */
                ['', '', ''],
                ['Grace Hopper', 'not-an-email', Role::User->value],
            ]),
        ])
        ->assertSessionHasErrors('rows.4.email')
        ->assertSessionDoesntHaveErrors('rows.3.email');
});

it('rejects a sheet that collides with itself', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([
                ['Ada Lovelace', 'ada@example.com', Role::User->value],
                ['Ada Again', 'ADA@example.com', Role::User->value],
            ]),
        ])
        ->assertSessionHasErrors('rows.2.email');

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('rejects an email that already belongs to an account', function () {
    $existing = User::factory()->create();

    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([['Ada Lovelace', $existing->email, Role::User->value]]),
        ])
        ->assertSessionHasErrors('rows.2.email');

    expect(User::query()->where('email', $existing->email)->count())->toBe(1);
});

it('rejects a role that does not exist', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([['Ada Lovelace', 'ada@example.com', 'sorcerer']]),
        ])
        ->assertSessionHasErrors('rows.2.role');
});

/*
 * Import authorizes once for the whole file, so the per-row role check is the
 * only thing standing between a spreadsheet and a grant its author may not make.
 */
it('refuses a role the actor may not assign', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([['Ada Lovelace', 'ada@example.com', Role::SuperAdmin->value]]),
        ])
        ->assertSessionHasErrors('rows.2.role');

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('refuses to import at all without a configured default password', function () {
    $settings = app(UserProvisioningSettings::class);
    $settings->defaultPassword = null;
    $settings->save();

    actingAs(importer())
        ->post(route('master-data.users.import'), [
            'sheet' => importSheet([['Ada Lovelace', 'ada@example.com', Role::User->value]]),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('tells the table whether importing can provision anything', function () {
    actingAs(importer())
        ->get(route('master-data.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('hasDefaultPassword', true));

    $settings = app(UserProvisioningSettings::class);
    $settings->defaultPassword = null;
    $settings->save();

    actingAs(importer())
        ->get(route('master-data.users.index'))
        ->assertInertia(fn ($page) => $page->where('hasDefaultPassword', false));
});

it('offers a template shaped exactly like the sheet the parser reads', function () {
    $response = actingAs(importer())
        ->get(route('master-data.users.import.template'))
        ->assertOk()
        ->assertDownload('users-import-template.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'template-test').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    try {
        $rows = SimpleExcelReader::create($path, 'xlsx')->getRows()->all();

        expect(array_keys($rows[0]))->toBe(UserImportSheet::COLUMNS)
            /* The example role is one this actor is actually allowed to grant. */
            ->and($rows[0]['role'])->not->toBe(Role::SuperAdmin->value);
    } finally {
        unlink($path);
    }
});

it('keeps the template behind the create permission', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(PermissionModel::findOrCreate(Permission::ViewUsers->value, 'web'));

    actingAs($viewer)
        ->get(route('master-data.users.import.template'))
        ->assertForbidden();
});

it('requires a file at all', function () {
    actingAs(importer())
        ->post(route('master-data.users.import'), [])
        ->assertSessionHasErrors('sheet');
});

it('keeps the import out of reach of a guest', function () {
    post(route('master-data.users.import'), ['sheet' => importSheet([
        ['Ada Lovelace', 'ada@example.com', Role::User->value],
    ])])->assertRedirect(route('login'));

    get(route('master-data.users.import.template'))->assertRedirect(route('login'));
});
