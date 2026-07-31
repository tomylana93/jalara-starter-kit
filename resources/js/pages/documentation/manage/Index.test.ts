import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    DocumentationCategory,
    DocumentationSummary,
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

const account: DocumentationCategory = {
    id: 'category-1',
    name: 'Account',
    position: 1,
    documentations_count: 2,
};

const billing: DocumentationCategory = {
    id: 'category-2',
    name: 'Billing',
    position: 2,
    documentations_count: 1,
};

/* The server hands these over already ordered by category then document position. */
const documentations: DocumentationSummary[] = [
    {
        id: 'document-1',
        title: 'Reset password',
        slug: 'reset-password',
        status: 'published',
        position: 1,
        category: account,
    },
    {
        id: 'document-2',
        title: 'Change email',
        slug: 'change-email',
        status: 'draft',
        position: 2,
        category: account,
    },
    {
        id: 'document-3',
        title: 'Read an invoice',
        slug: 'read-an-invoice',
        status: 'published',
        position: 1,
        category: billing,
    },
];

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
    categories: DocumentationCategory[];
    documentations: DocumentationSummary[];
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
            documentations,
        });
        const rows = wrapper.findAll('[data-test="documentation-row"]');

        expect(wrapper.find('table').exists()).toBe(true);
        expect(rows).toHaveLength(3);
        expect(rows.map((row) => row.text())).toEqual([
            expect.stringContaining('Reset password'),
            expect.stringContaining('Change email'),
            expect.stringContaining('Read an invoice'),
        ]);
        /* Category position wins over document position across the whole table. */
        expect(rows[0]?.text()).toContain('Account');
        expect(rows[2]?.text()).toContain('Billing');
    });

    it('translates the table headings and the status badge', () => {
        const wrapper = mountManage({
            categories: [account],
            documentations,
        });

        expect(wrapper.text()).toContain('documentation.manage.column.title');
        expect(wrapper.text()).toContain('documentation.manage.column.status');
        expect(wrapper.text()).toContain('documentation.status.published');
        expect(wrapper.text()).toContain('documentation.status.draft');
    });

    it('renders the translated empty row when nothing is stored yet', () => {
        const wrapper = mountManage({
            categories: [account],
            documentations: [],
        });

        expect(wrapper.findAll('[data-test="documentation-row"]')).toHaveLength(
            0,
        );
        expect(wrapper.text()).toContain('documentation.empty.manage');
    });

    it('moves a document through its wayfinder route', async () => {
        const wrapper = mountManage({
            categories: [account],
            documentations,
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
            documentations,
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
            documentations,
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
            documentations,
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
});
