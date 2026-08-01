import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    DocumentationManagementCategory,
    DocumentationManagementRow,
    DocumentationTablePayload,
} from '@/types/documentation';
import Index from './Index.vue';

type Breadcrumb = { title: string; href: { url: string } };

const breadcrumbs = (): Breadcrumb[] =>
    (
        Index as unknown as {
            layout: (props: { locale: string; fallbackLocale: string }) => {
                breadcrumbs: Breadcrumb[];
            };
        }
    ).layout({ locale: 'en', fallbackLocale: 'en' }).breadcrumbs;

const account: DocumentationManagementCategory = {
    id: 'category-1',
    name: 'Account',
    position: 1,
    documentations_count: 2,
};

const billing: DocumentationManagementCategory = {
    id: 'category-2',
    name: 'Billing',
    position: 2,
    documentations_count: 1,
};

/* The server hands these over already ordered by category then document position. */
const rows: DocumentationManagementRow[] = [
    {
        id: 'document-1',
        title: 'Reset password',
        slug: 'reset-password',
        status: 'published',
        category: { id: account.id, name: account.name },
    },
    {
        id: 'document-2',
        title: 'Change email',
        slug: 'change-email',
        status: 'draft',
        category: { id: account.id, name: account.name },
    },
    {
        id: 'document-3',
        title: 'Read an invoice',
        slug: 'read-an-invoice',
        status: 'published',
        category: { id: billing.id, name: billing.name },
    },
];

/* One page of the server-driven table, in the payload contract it publishes. */
const payload = (
    data: DocumentationManagementRow[],
    meta: Partial<DocumentationTablePayload['meta']> = {},
): DocumentationTablePayload => ({
    data,
    meta: {
        page: 1,
        perPage: 10,
        perPageOptions: [10, 25, 50],
        total: data.length,
        lastPage: 1,
        from: data.length === 0 ? null : 1,
        to: data.length === 0 ? null : data.length,
        ...meta,
    },
    state: {
        search: null,
        sort: 'position',
        direction: 'asc',
        perPage: 10,
        filters: {},
    },
});

const passthroughStub = { template: '<div><slot /></div>' };

const dialogStubs = {
    Dialog: {
        props: ['open'],
        template: '<div v-if="open" data-test="rename-dialog"><slot /></div>',
    },
    DialogContent: passthroughStub,
    DialogHeader: passthroughStub,
    DialogTitle: passthroughStub,
    DialogDescription: passthroughStub,
    DialogFooter: passthroughStub,
};

function mountManage(props: {
    categories: DocumentationManagementCategory[];
    documentations: DocumentationTablePayload;
}) {
    return mount(Index, {
        props,
        global: { stubs: dialogStubs },
    });
}

describe('documentation management', () => {
    beforeEach(() => {
        vi.spyOn(router, 'visit').mockImplementation(
            () => undefined as unknown as ReturnType<typeof router.visit>,
        );
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('nests the management page under the documentation breadcrumb', () => {
        const items = breadcrumbs();

        expect(items).toHaveLength(2);
        expect(items[0]?.title).toBe('documentation.title');
        expect(items[0]?.href.url).toBe('/documentation');
        expect(items[1]?.title).toBe('documentation.manage.title');
        expect(items[1]?.href.url).toBe('/documentation/manage');
    });

    it('lists documents in a table following the order the server sent', () => {
        const wrapper = mountManage({
            categories: [account, billing],
            documentations: payload(rows),
        });
        const rendered = wrapper.findAll('[data-test="documentation-row"]');

        expect(wrapper.find('table').exists()).toBe(true);
        expect(rendered).toHaveLength(3);
        expect(rendered.map((row) => row.text())).toEqual([
            expect.stringContaining('Reset password'),
            expect.stringContaining('Change email'),
            expect.stringContaining('Read an invoice'),
        ]);
        /* Category position wins over document position across the whole table. */
        expect(rendered[0]?.text()).toContain('Account');
        expect(rendered[2]?.text()).toContain('Billing');
    });

    it('translates the table headings and the status badge', () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows),
        });

        expect(wrapper.text()).toContain('documentation.manage.column.title');
        expect(wrapper.text()).toContain('documentation.manage.column.status');
        expect(wrapper.text()).toContain('documentation.status.published');
        expect(wrapper.text()).toContain('documentation.status.draft');
    });

    it('renders the translated empty row when nothing is stored yet', () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: payload([]),
        });

        expect(wrapper.findAll('[data-test="documentation-row"]')).toHaveLength(
            0,
        );
        expect(wrapper.text()).toContain('documentation.empty.manage');
    });

    it('moves a document through its wayfinder route', async () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows),
        });

        await wrapper
            .get('[aria-label="documentation.manage.document.move_down"]')
            .trigger('click');

        expect(router.visit).toHaveBeenCalledWith(
            expect.objectContaining({
                url: '/documentation/manage/documents/reset-password/move/down',
                method: 'post',
            }),
        );
    });

    it('renames a category through a dialog instead of a browser prompt', async () => {
        const prompt = vi.fn();
        vi.stubGlobal('prompt', prompt);
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows),
        });

        expect(wrapper.find('[data-test="rename-dialog"]').exists()).toBe(
            false,
        );

        await wrapper
            .get('[aria-label="documentation.manage.category.rename"]')
            .trigger('click');

        expect(prompt).not.toHaveBeenCalled();
        expect(wrapper.find('[data-test="rename-dialog"]').exists()).toBe(true);
        expect(
            wrapper
                .get('[data-test="rename-category-input"]')
                .attributes('value'),
        ).toBe('Account');
        vi.unstubAllGlobals();
    });

    it('confirms a category deletion through the alert dialog, not window.confirm', async () => {
        const confirm = vi.fn();
        vi.stubGlobal('confirm', confirm);
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows),
        });

        await wrapper
            .get('[aria-label="documentation.manage.category.delete"]')
            .trigger('click');
        await wrapper
            .get('[data-test="confirm-category-delete"]')
            .trigger('click');

        expect(confirm).not.toHaveBeenCalled();
        expect(router.visit).toHaveBeenCalledWith(
            expect.objectContaining({
                url: '/documentation/manage/categories/category-1',
                method: 'delete',
            }),
            expect.objectContaining({ preserveScroll: true }),
        );
        vi.unstubAllGlobals();
    });

    it('deletes a document permanently after the alert dialog is confirmed', async () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows),
        });

        await wrapper
            .get('[aria-label="documentation.manage.document.delete"]')
            .trigger('click');
        await wrapper
            .get('[data-test="confirm-document-delete"]')
            .trigger('click');

        expect(router.visit).toHaveBeenCalledWith(
            expect.objectContaining({
                url: '/documentation/manage/documents/reset-password',
                method: 'delete',
            }),
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('renders the server row window instead of counting the rows it received', () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows, {
                page: 2,
                total: 23,
                lastPage: 3,
                from: 11,
                to: 13,
            }),
        });

        expect(wrapper.get('[data-test="documentation-summary"]').text()).toBe(
            'common.table.summary',
        );
        expect(
            wrapper.find('[data-test="documentation-page-3"]').exists(),
        ).toBe(true);
    });

    it('hides the pager while a single page holds every document', () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows),
        });

        expect(
            wrapper.find('[data-test="documentation-next-page"]').exists(),
        ).toBe(false);
        expect(
            wrapper.find('[data-test="documentation-summary"]').exists(),
        ).toBe(true);
    });

    it('navigates to another page through the management route', async () => {
        const get = vi
            .spyOn(router, 'get')
            .mockImplementation(
                () => undefined as unknown as ReturnType<typeof router.get>,
            );
        const wrapper = mountManage({
            categories: [account],
            documentations: payload(rows, {
                page: 1,
                total: 23,
                lastPage: 3,
                from: 1,
                to: 10,
            }),
        });

        await wrapper
            .get('[data-test="documentation-page-2"]')
            .trigger('click');

        expect(get).toHaveBeenCalledWith(
            expect.objectContaining({ url: '/documentation/manage?page=2' }),
            {},
            expect.objectContaining({
                preserveScroll: true,
                preserveState: true,
                replace: true,
                only: ['documentations'],
            }),
        );
    });
});
