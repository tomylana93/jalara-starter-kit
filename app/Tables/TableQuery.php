<?php

namespace App\Tables;

/**
 * The normalized state of a server-side table request.
 *
 * Every value is coerced into the allowed set here so a table implementation
 * never has to defend against untrusted input. The sort key stays a client
 * facing identifier; translating it to a database column is the table's job.
 */
final readonly class TableQuery
{
    public const int DEFAULT_PER_PAGE = 10;

    public const array PER_PAGE_OPTIONS = [10, 25, 50];

    public const array DIRECTIONS = ['asc', 'desc'];

    /**
     * @param  'asc'|'desc'  $direction
     * @param  array<string, list<string>>  $filters
     */
    public function __construct(
        public ?string $search = null,
        public ?string $sort = null,
        public string $direction = 'desc',
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
        public array $filters = [],
    ) {}

    /**
     * Build a query from already validated request data.
     *
     * Filters stay domain free here: the caller names the keys its table
     * understands, and every other input key is discarded.
     *
     * @param  array<string, mixed>  $validated
     * @param  list<string>  $filterKeys
     */
    public static function fromValidated(array $validated, array $filterKeys = []): self
    {
        $search = $validated['search'] ?? null;
        $search = is_string($search) ? trim($search) : null;

        $sort = $validated['sort'] ?? null;
        $direction = $validated['direction'] ?? null;
        $perPage = (int) ($validated['perPage'] ?? self::DEFAULT_PER_PAGE);
        $page = (int) ($validated['page'] ?? 1);

        return new self(
            search: ($search === null || $search === '') ? null : $search,
            sort: is_string($sort) && $sort !== '' ? $sort : null,
            direction: in_array($direction, self::DIRECTIONS, true) ? $direction : 'desc',
            page: max(1, $page),
            perPage: in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE,
            filters: self::normalizeFilters($validated, $filterKeys),
        );
    }

    /**
     * Return a copy with the sort key and direction resolved to a known column.
     *
     * @param  'asc'|'desc'  $direction
     */
    public function withResolvedSort(string $sort, string $direction): self
    {
        return new self(
            search: $this->search,
            sort: $sort,
            direction: $direction,
            page: $this->page,
            perPage: $this->perPage,
            filters: $this->filters,
        );
    }

    /**
     * Reduce the requested filters to distinct, non-empty string lists.
     *
     * A filter that selects nothing is dropped entirely so an empty list never
     * has to mean "match everything" further down.
     *
     * @param  array<string, mixed>  $validated
     * @param  list<string>  $filterKeys
     * @return array<string, list<string>>
     */
    private static function normalizeFilters(array $validated, array $filterKeys): array
    {
        $filters = [];

        foreach ($filterKeys as $key) {
            $values = $validated[$key] ?? null;

            if (! is_array($values)) {
                continue;
            }

            $values = array_values(array_unique(array_filter(
                array_map(fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '', $values),
                fn (string $value): bool => $value !== '',
            )));

            if ($values !== []) {
                $filters[$key] = $values;
            }
        }

        return $filters;
    }
}
