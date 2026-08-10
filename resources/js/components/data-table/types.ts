export type TableSortDirection = 'asc' | 'desc';

/**
 * The slice of a TanStack column the sortable header actually needs.
 *
 * Depending on the shape rather than `Column<TData, TValue>` keeps the header
 * usable from a plain column definition, where the row type cannot be inferred.
 */
export type SortableColumn = {
    id: string;
    getCanSort: () => boolean;
    getIsSorted: () => false | TableSortDirection;
    toggleSorting: (desc?: boolean) => void;
};

/**
 * One selectable value of a filter, already labelled by the page.
 */
export type TableFilterOption = {
    value: string;
    label: string;
};

/**
 * A filter the toolbar offers, named by the key the server validates.
 *
 * The table stays domain free: it never knows what a key means, only that the
 * server understands it.
 */
export type TableFilterConfig = {
    key: string;
    label: string;
    options: TableFilterOption[];
};

/**
 * The selected values per filter key.
 *
 * Values within one key are alternatives; separate keys narrow together. A key
 * that selects nothing is absent rather than empty.
 */
export type TableFilters = Record<string, string[]>;

/**
 * The effective query the server resolved for the current page of rows.
 */
export type TableState = {
    search: string | null;
    sort: string;
    direction: TableSortDirection;
    perPage: number;
    filters: TableFilters;
};

export type TableMeta = {
    page: number;
    perPage: number;
    perPageOptions: number[];
    total: number;
    lastPage: number;
    from: number | null;
    to: number | null;
};

/**
 * The payload contract produced by a backend table.
 */
export type TablePayload<TRow> = {
    data: TRow[];
    meta: TableMeta;
    state: TableState;
};

/**
 * A complete query the parent should ask the server for.
 */
export type TableQuery = {
    search: string | null;
    sort: string;
    direction: TableSortDirection;
    page: number;
    perPage: number;
    filters: TableFilters;
};
