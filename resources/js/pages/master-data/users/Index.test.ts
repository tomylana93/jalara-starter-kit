import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { TablePayload } from '@/components/data-table';
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
    },
};

describe('master data user index', () => {
    beforeEach(() => {
        routerGet.mockClear();
    });

    it('renders every user with its role and status', () => {
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });

        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.text()).toContain('ada@example.com');
        expect(wrapper.text()).toContain('Admin');
        expect(wrapper.text()).toContain('Active');
        /* A user with no role still renders deterministically. */
        expect(wrapper.text()).toContain('master_data.user.role_missing');
    });

    it('renders the created instant in the browser timezone', () => {
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });

        /* 22:30 UTC on the 30th is 05:30 on the 31st in the test timezone. */
        expect(wrapper.text()).toContain('31/07/2026 05:30');
        /* A user with no timestamp still renders a safe fallback. */
        expect(wrapper.text()).toContain('—');
    });

    it('follows the configured date format preset', () => {
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'Y-m-d' },
        });

        expect(wrapper.text()).toContain('2026-07-31 05:30');
    });

    it('reports the ids of the rows selected on this page', async () => {
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });

        await wrapper.get('[data-test="table-select-all"]').trigger('click');

        expect(wrapper.text()).toContain('common.table.selected');
        expect(
            wrapper
                .get('[data-test="table-select-row-user-1"]')
                .attributes('aria-checked'),
        ).toBe('true');
    });

    it('offers the edit action only for a user that may be updated', () => {
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });

        expect(wrapper.find('[data-test="edit-user-user-1"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="edit-user-user-2"]').exists()).toBe(
            false,
        );
    });

    it('hides the create action without the permission', () => {
        const allowed = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });
        const denied = mount(Index, {
            props: { users, canCreate: false, dateFormat: 'd/m/Y' },
        });

        expect(allowed.find('[data-test="create-user-button"]').exists()).toBe(
            true,
        );
        expect(denied.find('[data-test="create-user-button"]').exists()).toBe(
            false,
        );
    });

    it('asks the server for a new page of rows and parks the query in the URL', () => {
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });

        wrapper.findComponent({ name: 'DataTable' }).vm.$emit('query-change', {
            search: 'ada',
            sort: 'name',
            direction: 'asc',
            page: 2,
            perPage: 25,
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
        const wrapper = mount(Index, {
            props: { users, canCreate: true, dateFormat: 'd/m/Y' },
        });

        wrapper.findComponent({ name: 'DataTable' }).vm.$emit('query-change', {
            search: null,
            sort: 'createdAt',
            direction: 'desc',
            page: 1,
            perPage: 10,
        });

        expect(routerGet.mock.calls[0]?.[0]).not.toContain('search');
    });
});
