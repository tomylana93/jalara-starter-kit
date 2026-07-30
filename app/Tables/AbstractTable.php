<?php

namespace App\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A server-side table: search, whitelisted sorting, and pagination.
 *
 * The client only ever names a sort *key*; `sortable()` maps that key to a
 * column, so a request can never reach an arbitrary column. Rows leave through
 * `transform()`, which keeps the payload an explicit contract instead of a
 * serialized model.
 *
 * @template TModel of Model
 */
abstract class AbstractTable
{
    /**
     * Paginate the table into the payload contract consumed by the client.
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{
     *         page: int,
     *         perPage: int,
     *         perPageOptions: list<int>,
     *         total: int,
     *         lastPage: int,
     *         from: int|null,
     *         to: int|null,
     *     },
     *     state: array{search: string|null, sort: string, direction: string, perPage: int},
     * }
     */
    public function paginate(TableQuery $tableQuery): array
    {
        $resolved = $this->resolveSort($tableQuery);
        $sortable = $this->sortable();

        $query = $this->query();

        if ($resolved->search !== null) {
            $query->whereAny($this->searchable(), 'like', '%'.$resolved->search.'%');
        }

        $query
            ->orderBy($sortable[(string) $resolved->sort], $resolved->direction)
            /* A secondary key keeps paging stable when the sorted values tie. */
            ->orderBy($this->tieBreaker(), $resolved->direction);

        /*
         * Laravel accepts any positive page, so a page past the end would answer
         * with an empty window instead of rows. Counting first lets the request
         * settle on the last page that exists; the count is handed to the
         * paginator so normalizing costs no extra query.
         */
        $total = $query->toBase()->getCountForPagination();
        $lastPage = max(1, (int) ceil($total / $resolved->perPage));

        $paginator = $query->paginate(
            perPage: $resolved->perPage,
            page: min($resolved->page, $lastPage),
            total: $total,
        );

        $page = $paginator->currentPage();
        $perPage = $paginator->perPage();
        $count = count($paginator->items());
        $from = $count === 0 ? null : (($page - 1) * $perPage) + 1;

        return [
            'data' => array_map(
                $this->transform(...),
                array_values($paginator->items()),
            ),
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
                'total' => $total,
                'lastPage' => max(1, $paginator->lastPage()),
                'from' => $from,
                'to' => $from === null ? null : ($from + $count) - 1,
            ],
            'state' => [
                'search' => $resolved->search,
                'sort' => (string) $resolved->sort,
                'direction' => $resolved->direction,
                'perPage' => $perPage,
            ],
        ];
    }

    /**
     * The base query the table paginates.
     *
     * @return Builder<TModel>
     */
    abstract protected function query(): Builder;

    /**
     * The columns matched by the free-text search term.
     *
     * @return list<string>
     */
    abstract protected function searchable(): array;

    /**
     * Client sort keys mapped to the columns they are allowed to order by.
     *
     * @return non-empty-array<string, string>
     */
    abstract protected function sortable(): array;

    /**
     * The sort key applied when the request names none, or names an unknown one.
     */
    abstract protected function defaultSort(): string;

    /**
     * Convert one record into the row contract sent to the client.
     *
     * @param  TModel  $model
     * @return array<string, mixed>
     */
    abstract protected function transform(Model $model): array;

    /**
     * The column that breaks ties between equal sort values.
     */
    protected function tieBreaker(): string
    {
        return 'id';
    }

    /**
     * Fall back to the default sort whenever the requested key is not allowed.
     */
    private function resolveSort(TableQuery $tableQuery): TableQuery
    {
        $sort = $tableQuery->sort;

        if ($sort === null || ! array_key_exists($sort, $this->sortable())) {
            $sort = $this->defaultSort();
        }

        return $tableQuery->withResolvedSort($sort, $tableQuery->direction);
    }
}
