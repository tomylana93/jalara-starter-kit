import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { TablePayload } from '@/components/data-table';
import { TEST_TIME_ZONE, withTimeZone } from '@/test/timeZone';
import type { UserRow } from './columns';
import Index from './Index.vue';

/* The shared setup keeps the real router, so only the visit is intercepted. */
const routerGet = vi.spyOn(router, 'get').mockImplementation(() => undefined);

const users: TablePayload<UserRow> = {
    data: [
        {
            id: 'user-1',
            name: 'Ada Lovelace',
            email: 'ada@example.com',
            role: { value: 'admin', label: 'Admin' },
            status: { value: 'active', label: 'Active', variant: 'default' },
            createdAt: '2026-07-30T22:30:00.000000Z',
            canUpdate: true,
        },
        {
            id: 'user-2',
            name: 'System Account',
            email: 'system@example.com',
            role: null,
            status: {
                value: 'disabled',
                label: 'Disabled',
                variant: 'destructive',
            },
            createdAt: null,
            canUpdate: false,
        },
    ],
    meta: {
        page: 1,
        perPage: 10,
        perPageOptions: [10, 25, 50],
        total: 2,
        lastPage: 1,
        from: 1,
        to: 2,
    },
    state: {
        search: null,
        sort: 'createdAt',
        direction: 'desc',
        perPage: 10,
        filters: {},
    },
};

const filterOptions = {
    status: [
        { value: 'active', label: 'Active' },
        { value: 'disabled', label: 'Disabled' },
    ],
    role: [
        { value: 'admin', label: 'Admin' },
        { value: 'user', label: 'User' },
    ],
};

/* Dialog content teleports out of the wrapper, so tests that reach into it
   attach the component to the document and query from there. */
const mountIndex = (
    overrides: Record<string, unknown> = {},
    attachTo?: HTMLElement,
) =>
    mount(Index, {
        attachTo,
        props: {
            users,
            filterOptions,
            canCreate: true,
            hasDefaultPassword: true,
            dateFormat: 'd/m/Y',
            ...overrides,
        },
    });

describe('master data user index', () => {
    beforeEach(() => {
        routerGet.mockClear();
    });

    it('renders every user with its role and status', () => {
        const wrapper = mountIndex({ canCreate: true, dateFormat: 'd/m/Y' });

        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.text()).toContain('ada@example.com');
        expect(wrapper.text()).toContain('Admin');
        expect(wrapper.text()).toContain('Active');
        /* A user with no role still renders deterministically. */
        expect(wrapper.text()).toContain('master_data.user.role_missing');
    });

    it('renders the created instant in the browser timezone', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            const wrapper = mountIndex({
                canCreate: true,
                dateFormat: 'd/m/Y',
            });

            /* 22:30 UTC on the 30th is 05:30 on the 31st in the pinned zone. */
            expect(wrapper.text()).toContain('31/07/2026 05:30');
            /* A user with no timestamp still renders a safe fallback. */
            expect(wrapper.text()).toContain('—');
        });
    });

    it('follows the configured date format preset', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            const wrapper = mountIndex({
                canCreate: true,
                dateFormat: 'Y-m-d',
            });

            expect(wrapper.text()).toContain('2026-07-31 05:30');
        });
    });

    it('reports the ids of the rows selected on this page', async () => {
        const wrapper = mountIndex({ canCreate: true, dateFormat: 'd/m/Y' });

        await wrapper.get('[data-test="table-select-all"]').trigger('click');

        expect(wrapper.text()).toContain('common.table.selected');
        expect(
            wrapper
                .get('[data-test="table-select-row-user-1"]')
                .attributes('aria-checked'),
        ).toBe('true');
    });

    it('offers a row action menu only for a user that may be updated', () => {
        const wrapper = mountIndex();

        expect(wrapper.find('[data-test="user-actions-user-1"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="user-actions-user-2"]').exists()).toBe(
            false,
        );
    });

    it('opens the edit action from the row action menu', async () => {
        const wrapper = mountIndex();

        await wrapper.get('[data-test="user-actions-user-1"]').trigger('click');

        /* The menu content is force mounted here, so only the link is asserted. */
        expect(
            wrapper.get('[data-test="edit-user-user-1"]').attributes('href'),
        ).toBe('/master-data/users/user-1/edit');
    });

    it('hides the create action without the permission', () => {
        const allowed = mountIndex({ canCreate: true, dateFormat: 'd/m/Y' });
        const denied = mountIndex({ canCreate: false, dateFormat: 'd/m/Y' });

        expect(allowed.find('[data-test="create-user-button"]').exists()).toBe(
            true,
        );
        expect(denied.find('[data-test="create-user-button"]').exists()).toBe(
            false,
        );
    });

    it('asks the server for a new page of rows and parks the query in the URL', () => {
        const wrapper = mountIndex({ canCreate: true, dateFormat: 'd/m/Y' });

        wrapper.findComponent({ name: 'DataTable' }).vm.$emit('query-change', {
            search: 'ada',
            sort: 'name',
            direction: 'asc',
            page: 2,
            perPage: 25,
            filters: {},
        });

        expect(routerGet).toHaveBeenCalledTimes(1);

        const [url, data, options] = routerGet.mock.calls[0] ?? [];

        expect(url).toBe(
            '/master-data/users?search=ada&sort=name&direction=asc&page=2&perPage=25',
        );
        expect(data).toEqual({});
        expect(options).toMatchObject({
            only: ['users'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    });

    it('omits an absent search term from the URL', () => {
        const wrapper = mountIndex({ canCreate: true, dateFormat: 'd/m/Y' });

        wrapper.findComponent({ name: 'DataTable' }).vm.$emit('query-change', {
            search: null,
            sort: 'createdAt',
            direction: 'desc',
            page: 1,
            perPage: 10,
            filters: {},
        });

        expect(routerGet.mock.calls[0]?.[0]).not.toContain('search');
    });

    it('renders a filter for the status and role catalogs', () => {
        const wrapper = mountIndex();

        expect(wrapper.find('[data-test="table-filter-status"]').exists()).toBe(
            true,
        );
        expect(
            wrapper.get('[data-test="table-filter-role-admin"]').text(),
        ).toBe('Admin');
    });

    it('parks the selected filters in the URL', () => {
        const wrapper = mountIndex();

        wrapper.findComponent({ name: 'DataTable' }).vm.$emit('query-change', {
            search: null,
            sort: 'createdAt',
            direction: 'desc',
            page: 1,
            perPage: 10,
            filters: { status: ['active', 'disabled'], role: ['admin'] },
        });

        const url = routerGet.mock.calls[0]?.[0] as string;

        expect(decodeURIComponent(url)).toContain('status[]=active');
        expect(decodeURIComponent(url)).toContain('status[]=disabled');
        expect(decodeURIComponent(url)).toContain('role[]=admin');
    });

    it('offers the export only once rows are selected', async () => {
        const wrapper = mountIndex();

        expect(wrapper.find('[data-test="export-users-button"]').exists()).toBe(
            false,
        );

        await wrapper.get('[data-test="table-select-all"]').trigger('click');

        expect(
            decodeURIComponent(
                wrapper
                    .get('[data-test="export-users-button"]')
                    .attributes('href') ?? '',
            ),
        ).toBe('/master-data/users/export?ids[]=user-1&ids[]=user-2');
    });

    it('offers the import alongside the create action', () => {
        const wrapper = mountIndex();

        expect(wrapper.find('[data-test="import-users-button"]').exists()).toBe(
            true,
        );
    });

    it('hides the import without the create permission', () => {
        const wrapper = mountIndex({ canCreate: false });

        expect(wrapper.find('[data-test="import-users-button"]').exists()).toBe(
            false,
        );
    });

    it('disables the import until a default password is configured', () => {
        const configured = mountIndex({ hasDefaultPassword: true });
        const missing = mountIndex({ hasDefaultPassword: false });

        expect(
            configured
                .get('[data-test="import-users-button"]')
                .attributes('disabled'),
        ).toBeUndefined();

        const button = missing.get('[data-test="import-users-button"]');

        expect(button.attributes('disabled')).toBeDefined();
        /* The affordance has to say why, or it reads as a broken button. */
        expect(button.attributes('title')).toBe(
            'master_data.user.import.message.password_missing',
        );
    });

    it('opens the import dialog with the template download', async () => {
        const wrapper = mountIndex({}, document.body);

        expect(
            document.querySelector('[data-test="download-import-template"]'),
        ).toBeNull();

        await wrapper.get('[data-test="import-users-button"]').trigger('click');

        expect(
            document
                .querySelector('[data-test="download-import-template"]')
                ?.getAttribute('href'),
        ).toBe('/master-data/users/import/template');
        expect(
            document
                .querySelector('[data-test="import-users-input"]')
                ?.getAttribute('type'),
        ).toBe('file');
    });

    it('keeps the import submit disabled until a file is chosen', async () => {
        const wrapper = mountIndex({}, document.body);

        await wrapper.get('[data-test="import-users-button"]').trigger('click');

        expect(
            document
                .querySelector('[data-test="confirm-import-users"]')
                ?.hasAttribute('disabled'),
        ).toBe(true);
    });

    it('exports a single selected row', async () => {
        const wrapper = mountIndex();

        await wrapper
            .get('[data-test="table-select-row-user-2"]')
            .trigger('click');

        expect(
            decodeURIComponent(
                wrapper
                    .get('[data-test="export-users-button"]')
                    .attributes('href') ?? '',
            ),
        ).toBe('/master-data/users/export?ids[]=user-2');
    });
});
