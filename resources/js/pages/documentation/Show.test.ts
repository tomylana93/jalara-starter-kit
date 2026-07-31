import { ArrowLeft } from '@lucide/vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import type {
    DocumentationCategory,
    DocumentationDetail,
} from '@/types/documentation';
import Show from './Show.vue';

type Breadcrumb = { title: string; href: { url: string } };

const documentation: DocumentationDetail = {
    id: 'document-1',
    title: 'Reset password',
    slug: 'reset-password',
    status: 'published',
    position: 1,
    documentation_category_id: 'category-1',
    published_at: '2026-07-31T00:00:00+00:00',
    content: { type: 'doc', content: [] },
    category: { id: 'category-1', name: 'Account', position: 1 },
};

const categories: DocumentationCategory[] = [
    {
        id: 'category-1',
        name: 'Account',
        position: 1,
        documentations: [documentation],
    },
];

const breadcrumbs = (): Breadcrumb[] =>
    (
        Show as unknown as {
            layout: (props: {
                locale: string;
                fallbackLocale: string;
                documentation: DocumentationDetail;
            }) => { breadcrumbs: Breadcrumb[] };
        }
    ).layout({
        locale: 'en',
        fallbackLocale: 'en',
        documentation,
    }).breadcrumbs;

const passthroughStub = { template: '<div><slot /></div>' };

describe('documentation reader', () => {
    it('nests the document title under the documentation breadcrumb', () => {
        const items = breadcrumbs();

        expect(items).toHaveLength(2);
        expect(items[0]?.title).toBe('documentation.title');
        expect(items[0]?.href.url).toBe('/documentation');
        expect(items[1]?.title).toBe('Reset password');
        expect(items[1]?.href.url).toBe('/documentation/reset-password');
    });

    it('renders translated navigation copy around the document', () => {
        const wrapper = mount(Show, {
            props: { documentation, categories },
            global: { stubs: { ScrollArea: passthroughStub } },
        });

        expect(wrapper.text()).toContain('documentation.reader.list');
        expect(wrapper.text()).toContain(
            'documentation.reader.list_description',
        );
        expect(wrapper.get('h1').text()).toBe('Reset password');
        expect(wrapper.text()).toContain('Account');

        // Verify the desktop back link
        const backLink = wrapper.find('a[href="/documentation"]');
        expect(backLink.exists()).toBe(true);
        expect(backLink.text()).toContain('documentation.title');
        expect(backLink.text()).not.toContain('←');
        expect(backLink.findComponent(ArrowLeft).exists()).toBe(true);
    });
});
