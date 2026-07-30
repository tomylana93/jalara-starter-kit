import type { RowData } from '@tanstack/vue-table';

declare module '@tanstack/vue-table' {
    /**
     * The visibility menu is domain-free, so a hideable column carries its own
     * already-translated label here.
     */
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<TData extends RowData, TValue> {
        label?: string;
    }
}

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
 * The effective query the server resolved for the current page of rows.
 */
export type TableState = {
    search: string | null;
    sort: string;
    direction: TableSortDirection;
    perPage: number;
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
};
