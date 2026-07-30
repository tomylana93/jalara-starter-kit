<?php

use App\Models\User;
use App\Tables\TableQuery;
use App\Tables\UsersTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * The query normalization below is database free, but exercising the executor
 * itself needs a real query, so the file binds the application test case.
 */
uses(TestCase::class, RefreshDatabase::class);

function usersTable(): UsersTable
{
    return new UsersTable(User::factory()->create());
}

it('falls back to the documented defaults', function () {
    $query = TableQuery::fromValidated([]);

    expect($query->search)->toBeNull()
        ->and($query->sort)->toBeNull()
        ->and($query->direction)->toBe('desc')
        ->and($query->page)->toBe(1)
        ->and($query->perPage)->toBe(TableQuery::DEFAULT_PER_PAGE);
});

it('trims a search term and treats a blank one as absent', function (mixed $search) {
    expect(TableQuery::fromValidated(['search' => $search])->search)->toBeNull();
})->with(['empty' => [''], 'blank' => ['   '], 'missing' => [null]]);

it('keeps a trimmed search term', function () {
    expect(TableQuery::fromValidated(['search' => '  ada  '])->search)->toBe('ada');
});

it('ignores a direction outside the allowed set', function () {
    expect(TableQuery::fromValidated(['direction' => 'sideways'])->direction)->toBe('desc')
        ->and(TableQuery::fromValidated(['direction' => 'asc'])->direction)->toBe('asc');
});

it('ignores a page size outside the offered options', function () {
    expect(TableQuery::fromValidated(['perPage' => 1000])->perPage)->toBe(TableQuery::DEFAULT_PER_PAGE)
        ->and(TableQuery::fromValidated(['perPage' => '25'])->perPage)->toBe(25);
});

it('never resolves a page below the first', function () {
    expect(TableQuery::fromValidated(['page' => 0])->page)->toBe(1)
        ->and(TableQuery::fromValidated(['page' => -5])->page)->toBe(1);
});

it('falls back to the default sort when the key is not whitelisted', function () {
    $result = usersTable()->paginate(new TableQuery(sort: 'password'));

    expect($result['state']['sort'])->toBe('createdAt');
});

it('orders by a whitelisted key mapped to its column', function () {
    User::factory()->create(['name' => 'Aaron First']);
    User::factory()->create(['name' => 'Zoe Last']);

    $result = usersTable()->paginate(new TableQuery(sort: 'name', direction: 'asc', perPage: 50));

    expect($result['data'][0]['name'])->toBe('Aaron First');
});

it('matches the search term against every searchable column', function () {
    User::factory()->create(['name' => 'Unrelated', 'email' => 'needle@example.com']);
    User::factory()->create(['name' => 'Needle Person', 'email' => 'unrelated@example.com']);

    $result = usersTable()->paginate(new TableQuery(search: 'needle', perPage: 50));

    expect($result['data'])->toHaveCount(2);
});

it('pages deterministically when the sorted values tie', function () {
    $table = usersTable();
    User::factory()->count(12)->create(['name' => 'Identical Name']);

    $first = $table->paginate(new TableQuery(sort: 'name', page: 1, perPage: 10));
    $second = $table->paginate(new TableQuery(sort: 'name', page: 2, perPage: 10));

    $ids = [
        ...array_column($first['data'], 'id'),
        ...array_column($second['data'], 'id'),
    ];

    expect($ids)->toHaveCount(13)
        ->and(array_unique($ids))->toHaveCount(13);
});

it('reports the row window alongside the page of data', function () {
    User::factory()->count(12)->create();

    $result = usersTable()->paginate(new TableQuery(page: 2, perPage: 10));

    expect($result['meta']['total'])->toBe(13)
        ->and($result['meta']['lastPage'])->toBe(2)
        ->and($result['meta']['from'])->toBe(11)
        ->and($result['meta']['to'])->toBe(13)
        ->and($result['meta']['perPageOptions'])->toBe(TableQuery::PER_PAGE_OPTIONS);
});

it('settles on the last page when the requested page is past the end', function () {
    User::factory()->count(12)->create();

    $result = usersTable()->paginate(new TableQuery(page: 999, perPage: 10));

    expect($result['data'])->toHaveCount(3)
        ->and($result['meta']['page'])->toBe(2)
        ->and($result['meta']['lastPage'])->toBe(2)
        ->and($result['meta']['total'])->toBe(13)
        ->and($result['meta']['from'])->toBe(11)
        ->and($result['meta']['to'])->toBe(13);
});

it('settles on the first page when nothing matches at all', function () {
    $result = usersTable()->paginate(new TableQuery(search: 'no-such-user', page: 999));

    expect($result['data'])->toBe([])
        ->and($result['meta']['page'])->toBe(1)
        ->and($result['meta']['lastPage'])->toBe(1)
        ->and($result['meta']['from'])->toBeNull()
        ->and($result['meta']['to'])->toBeNull();
});

it('reports an empty window when no row matches', function () {
    $result = usersTable()->paginate(new TableQuery(search: 'no-such-user'));

    expect($result['data'])->toBe([])
        ->and($result['meta']['total'])->toBe(0)
        ->and($result['meta']['from'])->toBeNull()
        ->and($result['meta']['to'])->toBeNull();
});
