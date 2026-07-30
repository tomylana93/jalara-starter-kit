import type { ColumnDef } from '@tanstack/vue-table';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { h } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import DataTable from './DataTable.vue';
import DataTableColumnHeader from './DataTableColumnHeader.vue';
import DataTablePagination from './DataTablePagination.vue';
import type { TablePayload } from './types';

type Row = {
    id: string;
    name: string;
};

const columns: ColumnDef<Row>[] = [
    {
        id: 'select',
        enableSorting: false,
        enableHiding: false,
        header: ({ table }) =>
            h(Checkbox, {
                modelValue:
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    table.toggleAllPageRowsSelected(!!value),
                'aria-label': 'Select all',
                'data-test': 'table-select-all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    row.toggleSelected(!!value),
                'aria-label': 'Select row',
                'data-test': `table-select-row-${row.id}`,
            }),
    },
    {
        accessorKey: 'name',
        meta: { label: 'Name' },
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Name' }),
        cell: ({ row }) => h('span', row.original.name),
    },
    {
        id: 'actions',
        enableSorting: false,
        enableHiding: false,
        header: () => 'Actions',
        cell: () => h('span', 'edit'),
    },
];

const payload = (
    overrides: Partial<TablePayload<Row>> = {},
): TablePayload<Row> => ({
    data: [
        { id: 'uuid-b', name: 'Zoe' },
        { id: 'uuid-a', name: 'Aaron' },
    ],
    meta: {
        page: 1,
        perPage: 10,
        perPageOptions: [10, 25, 50],
        total: 42,
        lastPage: 5,
        from: 1,
        to: 2,
    },
    state: {
        search: null,
        sort: 'name',
        direction: 'asc',
        perPage: 10,
    },
    ...overrides,
});

const mountTable = (tablePayload: TablePayload<Row> = payload()) =>
    mount(DataTable<Row, unknown>, {
        props: {
            columns,
            payload: tablePayload,
            getRowId: (row: Row) => row.id,
        },
    });

describe('DataTable', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders the rows exactly as the server ordered them', () => {
        const wrapper = mountTable();
        const rows = wrapper.findAll('tbody tr');

        expect(rows).toHaveLength(2);
        /* Server order is preserved even though the state claims ascending. */
        expect(rows[0]?.text()).toContain('Zoe');
        expect(rows[1]?.text()).toContain('Aaron');
    });

    it('identifies rows by the domain id rather than the row index', () => {
        const wrapper = mountTable();

        expect(wrapper.find('[data-test="table-row-uuid-b"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="table-row-0"]').exists()).toBe(false);
    });

    it('reflects the server sort state on the sortable header', () => {
        const wrapper = mountTable();

        expect(wrapper.find('[data-test="sort-name"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="sort-actions"]').exists()).toBe(false);
    });

    it('asks the server for the reversed sort and resets to the first page', async () => {
        const wrapper = mountTable(
            payload({ meta: { ...payload().meta, page: 3 } }),
        );

        await wrapper.get('[data-test="sort-name"]').trigger('click');

        expect(wrapper.emitted('query-change')?.at(0)).toEqual([
            {
                search: null,
                sort: 'name',
                direction: 'desc',
                page: 1,
                perPage: 10,
            },
        ]);
    });

    it('debounces a search term and resets to the first page', async () => {
        const wrapper = mountTable(
            payload({ meta: { ...payload().meta, page: 4 } }),
        );

        await wrapper.get('[data-test="table-search"]').setValue('ada');

        expect(wrapper.emitted('query-change')).toBeUndefined();

        await vi.advanceTimersByTimeAsync(300);

        expect(wrapper.emitted('query-change')?.at(0)).toEqual([
            {
                search: 'ada',
                sort: 'name',
                direction: 'asc',
                page: 1,
                perPage: 10,
            },
        ]);
    });

    it('clears the search term back to an absent filter', async () => {
        const wrapper = mountTable(
            payload({ state: { ...payload().state, search: 'ada' } }),
        );

        await wrapper.get('[data-test="table-search"]').setValue('   ');
        await vi.advanceTimersByTimeAsync(300);

        expect(wrapper.emitted('query-change')?.at(0)?.[0]).toMatchObject({
            search: null,
            page: 1,
        });
    });

    it('keeps an earlier change when two interactions overlap one round trip', async () => {
        const wrapper = mountTable();

        await wrapper.get('[data-test="sort-name"]').trigger('click');
        /* The server has not answered yet, so props still carry the old sort. */
        wrapper.findComponent(DataTablePagination).vm.$emit('update:page', 3);

        expect(wrapper.emitted('query-change')?.at(1)?.[0]).toMatchObject({
            sort: 'name',
            direction: 'desc',
            page: 3,
        });
    });

    it('keeps a search term still awaiting its debounce when a response lands', async () => {
        const wrapper = mountTable();

        await wrapper.get('[data-test="table-search"]').setValue('ada');
        /* A response for an earlier request arrives mid-typing. */
        await wrapper.setProps({
            payload: payload({ meta: { ...payload().meta, page: 2 } }),
        });

        expect(
            (
                wrapper.get('[data-test="table-search"]')
                    .element as HTMLInputElement
            ).value,
        ).toBe('ada');

        await wrapper.get('[data-test="sort-name"]').trigger('click');

        expect(wrapper.emitted('query-change')?.at(0)?.[0]).toMatchObject({
            search: 'ada',
        });
    });

    it('resets to the first page when the page size changes', () => {
        const wrapper = mountTable(
            payload({ meta: { ...payload().meta, page: 3 } }),
        );

        wrapper
            .findComponent(DataTablePagination)
            .vm.$emit('update:perPage', 25);

        expect(wrapper.emitted('query-change')?.at(0)?.[0]).toMatchObject({
            perPage: 25,
            page: 1,
        });
    });

    it('selects every row on the current page from the header checkbox', async () => {
        const wrapper = mountTable();

        await wrapper.get('[data-test="table-select-all"]').trigger('click');

        expect(wrapper.emitted('selection-change')?.at(-1)).toEqual([
            ['uuid-b', 'uuid-a'],
        ]);
    });

    it('emits the domain id of a single selected row', async () => {
        const wrapper = mountTable();

        await wrapper
            .get('[data-test="table-select-row-uuid-a"]')
            .trigger('click');

        expect(wrapper.emitted('selection-change')?.at(-1)).toEqual([
            ['uuid-a'],
        ]);
    });

    it('marks the header checkbox indeterminate for a partial page selection', async () => {
        const wrapper = mountTable();

        await wrapper
            .get('[data-test="table-select-row-uuid-a"]')
            .trigger('click');

        expect(
            wrapper
                .get('[data-test="table-select-all"]')
                .attributes('aria-checked'),
        ).toBe('mixed');

        await wrapper
            .get('[data-test="table-select-row-uuid-b"]')
            .trigger('click');

        expect(
            wrapper
                .get('[data-test="table-select-all"]')
                .attributes('aria-checked'),
        ).toBe('true');
    });

    it('carries an accessible label on every selection checkbox', () => {
        const wrapper = mountTable();

        expect(
            wrapper
                .get('[data-test="table-select-all"]')
                .attributes('aria-label'),
        ).toBe('Select all');
        expect(
            wrapper
                .get('[data-test="table-select-row-uuid-a"]')
                .attributes('aria-label'),
        ).toBe('Select row');
    });

    it('drops the selection when the server page changes', async () => {
        const wrapper = mountTable();

        await wrapper.get('[data-test="table-select-all"]').trigger('click');
        expect(wrapper.emitted('selection-change')?.at(-1)?.[0]).toHaveLength(
            2,
        );

        await wrapper.setProps({
            payload: payload({
                data: [{ id: 'uuid-c', name: 'Grace' }],
                meta: { ...payload().meta, page: 2 },
            }),
        });

        expect(wrapper.emitted('selection-change')?.at(-1)).toEqual([[]]);
        expect(
            wrapper
                .get('[data-test="table-select-row-uuid-c"]')
                .attributes('aria-checked'),
        ).toBe('false');
    });

    it('keeps the selection when only column visibility changes', async () => {
        const wrapper = mountTable();

        await wrapper.get('[data-test="table-select-all"]').trigger('click');
        await wrapper
            .get('[data-test="table-column-toggle-name"]')
            .trigger('click');

        expect(wrapper.emitted('selection-change')?.at(-1)).toEqual([
            ['uuid-b', 'uuid-a'],
        ]);
    });

    it('offers only hideable columns in the visibility menu', () => {
        const wrapper = mountTable();

        expect(
            wrapper.find('[data-test="table-column-toggle-name"]').exists(),
        ).toBe(true);
        expect(
            wrapper.find('[data-test="table-column-toggle-select"]').exists(),
        ).toBe(false);
        expect(
            wrapper.find('[data-test="table-column-toggle-actions"]').exists(),
        ).toBe(false);
    });

    it('labels a hideable column from its metadata', () => {
        const wrapper = mountTable();

        expect(
            wrapper.get('[data-test="table-column-toggle-name"]').text(),
        ).toBe('Name');
    });

    it('hides a column and narrows the rendered cells', async () => {
        const wrapper = mountTable();

        expect(wrapper.findAll('tbody tr').at(0)?.text()).toContain('Zoe');

        await wrapper
            .get('[data-test="table-column-toggle-name"]')
            .trigger('click');

        expect(wrapper.findAll('tbody tr').at(0)?.text()).not.toContain('Zoe');
    });

    it('spans the empty state across the visible columns only', async () => {
        const wrapper = mountTable(payload({ data: [] }));

        expect(
            wrapper.get('[data-test="table-empty"]').attributes('colspan'),
        ).toBe('3');

        await wrapper
            .get('[data-test="table-column-toggle-name"]')
            .trigger('click');

        expect(
            wrapper.get('[data-test="table-empty"]').attributes('colspan'),
        ).toBe('2');
    });

    it('renders an empty state instead of rows', () => {
        const wrapper = mountTable(
            payload({
                data: [],
                meta: {
                    ...payload().meta,
                    total: 0,
                    lastPage: 1,
                    from: null,
                    to: null,
                },
            }),
        );

        expect(wrapper.find('[data-test="table-empty"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('common.table.empty');
    });

    it('shows the supplied empty copy when provided', () => {
        const wrapper = mount(DataTable<Row, unknown>, {
            props: {
                columns,
                payload: payload({ data: [] }),
                getRowId: (row: Row) => row.id,
                emptyTitle: 'No users found',
                emptyDescription: 'Nothing matches the search.',
            },
        });

        expect(wrapper.text()).toContain('No users found');
        expect(wrapper.text()).toContain('Nothing matches the search.');
    });
});
