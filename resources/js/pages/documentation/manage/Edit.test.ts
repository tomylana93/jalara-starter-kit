import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import type {
    DocumentationCategoryOption,
    DocumentationEditorValue,
    DocumentationStatus,
} from '@/types/documentation';
import Edit from './Edit.vue';

type Breadcrumb = { title: string; href: { url: string } };

const documentation: DocumentationEditorValue = {
    id: 'document-1',
    title: 'Reset password',
    slug: 'reset-password',
    status: 'published',
    documentation_category_id: 'category-1',
    published_at: '2026-07-31T00:00:00+00:00',
    content: { type: 'doc', content: [] },
};

const categories: DocumentationCategoryOption[] = [
    { id: 'category-1', name: 'Account' },
];

const statuses: { value: DocumentationStatus; label: string }[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
];

const breadcrumbs = (target: DocumentationEditorValue | null): Breadcrumb[] =>
    (
        Edit as unknown as {
            layout: (props: {
                locale: string;
                fallbackLocale: string;
                documentation: DocumentationEditorValue | null;
            }) => { breadcrumbs: Breadcrumb[] };
        }
    ).layout({
        locale: 'en',
        fallbackLocale: 'en',
        documentation: target,
    }).breadcrumbs;

const editorStub = {
    template: '<div data-test="rich-text-editor" />',
};

function mountEditor(target: DocumentationEditorValue | null) {
    return mount(Edit, {
        props: { documentation: target, categories, statuses },
        global: { stubs: { RichTextEditor: editorStub } },
    });
}

describe('documentation editor page', () => {
    it('nests a new document under documentation and its management page', () => {
        const items = breadcrumbs(null);

        expect(items.map((item) => item.title)).toEqual([
            'documentation.title',
            'documentation.manage.title',
            'documentation.form.create',
        ]);
        expect(items[2]?.href.url).toBe('/documentation/manage/create');
    });

    it('points the last crumb of an existing document at its edit route', () => {
        const items = breadcrumbs(documentation);

        expect(items.map((item) => item.title)).toEqual([
            'documentation.title',
            'documentation.manage.title',
            'documentation.form.edit',
        ]);
        expect(items[2]?.href.url).toBe(
            '/documentation/manage/documents/reset-password/edit',
        );
    });

    it('translates the form labels and actions', () => {
        const wrapper = mountEditor(null);

        expect(wrapper.text()).toContain('documentation.form.label.title');
        expect(wrapper.text()).toContain('documentation.form.label.slug');
        expect(wrapper.text()).toContain('documentation.button.save');
        expect(wrapper.text()).toContain('documentation.button.cancel');
    });

    it('freezes the slug once the document has been published', () => {
        expect(
            mountEditor(documentation).get('#slug').attributes('disabled'),
        ).toBeDefined();
        expect(
            mountEditor(null).get('#slug').attributes('disabled'),
        ).toBeUndefined();
    });
});
