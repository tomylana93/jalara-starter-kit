import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import type { RichTextDocument } from '@/types/editor';
import DocumentationRenderer from './DocumentationRenderer.vue';

const managedSrc =
    'http://localhost/storage/documentation/upload-1/diagram.webp';

/**
 * Mounts the reader and waits for Tiptap to attach.
 *
 * `useEditor` creates the instance after mount, so the `v-if` guarding
 * `EditorContent` is still false on the first tick.
 */
async function renderDocument(content: RichTextDocument) {
    const wrapper = mount(DocumentationRenderer, {
        props: { content },
        attachTo: document.body,
    });

    await flushPromises();
    await nextTick();

    return wrapper;
}

describe('documentation renderer', () => {
    it('renders an image node with its alt text', async () => {
        const wrapper = await renderDocument({
            type: 'doc',
            content: [
                {
                    type: 'image',
                    attrs: { src: managedSrc, alt: 'Approval flow diagram' },
                },
            ],
        });

        const image = wrapper.get('img');

        expect(image.attributes('src')).toBe(managedSrc);
        expect(image.attributes('alt')).toBe('Approval flow diagram');
    });

    it('keeps the reader read-only', async () => {
        const wrapper = await renderDocument({
            type: 'doc',
            content: [
                {
                    type: 'image',
                    attrs: { src: managedSrc, alt: 'Approval flow diagram' },
                },
            ],
        });

        expect(
            wrapper.get('.rich-text-content').attributes('contenteditable'),
        ).toBe('false');
    });

    it('drops attributes an image node is not allowed to carry', async () => {
        const wrapper = await renderDocument({
            type: 'doc',
            content: [
                {
                    type: 'image',
                    attrs: {
                        src: managedSrc,
                        alt: 'Approval flow diagram',
                        /* Not in the schema, so it never reaches the DOM. */
                        onerror: 'alert(1)',
                        srcset: 'https://tracker.example/pixel.png',
                    },
                },
            ] as RichTextDocument['content'],
        });

        const image = wrapper.get('img');

        expect(image.attributes('onerror')).toBeUndefined();
        expect(image.attributes('srcset')).toBeUndefined();
    });

    it('still marks external links as safe to follow', async () => {
        const wrapper = await renderDocument({
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [
                        {
                            type: 'text',
                            text: 'Guide',
                            marks: [
                                {
                                    type: 'link',
                                    attrs: { href: 'https://example.com' },
                                },
                            ],
                        },
                    ],
                },
            ],
        });

        const anchor = wrapper.get('a');

        expect(anchor.attributes('target')).toBe('_blank');
        expect(anchor.attributes('rel')).toContain('noopener');
    });
});
