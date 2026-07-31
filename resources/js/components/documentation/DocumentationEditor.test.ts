import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { TiptapDocument } from '@/types/documentation';
import DocumentationEditor from './DocumentationEditor.vue';

const passthroughStub = { template: '<div><slot /></div>' };

const stubs = {
    Dialog: {
        props: ['open'],
        template: '<div v-if="open" data-test="link-dialog"><slot /></div>',
    },
    DialogContent: passthroughStub,
    DialogHeader: passthroughStub,
    DialogTitle: passthroughStub,
    DialogDescription: passthroughStub,
    DialogFooter: passthroughStub,
    TooltipProvider: passthroughStub,
    Tooltip: passthroughStub,
    TooltipTrigger: passthroughStub,
    TooltipContent: passthroughStub,
};

const modelValue: TiptapDocument = {
    type: 'doc',
    content: [{ type: 'paragraph' }],
};

function mountEditor() {
    return mount(DocumentationEditor, {
        props: { modelValue },
        global: { stubs },
    });
}

describe('documentation editor', () => {
    it('labels every toolbar action through the translation domain', () => {
        const wrapper = mountEditor();

        expect(
            wrapper
                .find('[aria-label="documentation.editor.action.bold"]')
                .exists(),
        ).toBe(true);
        expect(
            wrapper
                .find('[aria-label="documentation.editor.action.table"]')
                .exists(),
        ).toBe(true);
        expect(
            wrapper
                .find('[aria-label="documentation.editor.action.link"]')
                .exists(),
        ).toBe(true);
    });

    it('collects a link URL through a dialog instead of window.prompt', async () => {
        const prompt = vi.fn();
        vi.stubGlobal('prompt', prompt);
        const wrapper = mountEditor();

        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(false);

        await wrapper
            .get('[aria-label="documentation.editor.action.link"]')
            .trigger('click');

        expect(prompt).not.toHaveBeenCalled();
        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(true);
        expect(
            wrapper
                .get('[data-test="documentation-link-input"]')
                .attributes('value'),
        ).toBe('https://');
        vi.unstubAllGlobals();
    });

    it('closes the link dialog once a URL is applied', async () => {
        const wrapper = mountEditor();

        await wrapper
            .get('[aria-label="documentation.editor.action.link"]')
            .trigger('click');
        await wrapper
            .get('[data-test="documentation-link-input"]')
            .setValue('/internal-guide');
        await wrapper.get('[data-test="link-dialog"] form').trigger('submit');

        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(false);
    });
});
