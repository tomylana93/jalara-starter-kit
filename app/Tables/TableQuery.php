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
     */
    public function __construct(
        public ?string $search = null,
        public ?string $sort = null,
        public string $direction = 'desc',
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {}

    /**
     * Build a query from already validated request data.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
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
        );
    }
}
