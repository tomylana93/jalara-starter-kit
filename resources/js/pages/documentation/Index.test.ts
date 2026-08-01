import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import type { DocumentationReaderCategory } from '@/types/documentation';
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

const categories: DocumentationReaderCategory[] = [
    {
        id: 'category-1',
        name: 'Account',
        documentations: [
            {
                id: 'document-1',
                title: 'Reset password',
                slug: 'reset-password',
            },
        ],
    },
];

describe('documentation index', () => {
    afterEach(() => {
        inertiaPageProps.can.manageDocumentation = true;
    });

    it('exposes a documentation breadcrumb built from the wayfinder route', () => {
        const items = breadcrumbs();

        expect(items).toHaveLength(1);
        expect(items[0]?.title).toBe('documentation.title');
        expect(items[0]?.href.url).toBe('/documentation');
    });

    it('links every published document through translated reader copy', () => {
        const wrapper = mount(Index, { props: { categories } });

        expect(wrapper.text()).toContain('Account');
        expect(wrapper.text()).toContain('documentation.button.read');
        expect(
            wrapper.find('a[href="/documentation/reset-password"]').exists(),
        ).toBe(true);
    });

    it('renders the translated empty state without categories', () => {
        const wrapper = mount(Index, { props: { categories: [] } });

        expect(wrapper.text()).toContain('documentation.empty.reader');
    });

    it('hides the manage action from users without the ability', () => {
        inertiaPageProps.can.manageDocumentation = false;

        const wrapper = mount(Index, { props: { categories } });

        expect(wrapper.text()).not.toContain('documentation.button.manage');
        expect(wrapper.find('a[href="/documentation/manage"]').exists()).toBe(
            false,
        );
    });
});
