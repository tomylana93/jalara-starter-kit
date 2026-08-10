import type { Editor } from '@tiptap/vue-3';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import type * as ImageUploads from '@/lib/imageUploads';
import { ImageUploadError } from '@/lib/imageUploads';
import type { RichTextDocument } from '@/types/editor';
import RichTextEditor from './RichTextEditor.vue';

const uploadMocks = vi.hoisted(() => ({
    startImageUpload: vi.fn(),
    pollImageUpload: vi.fn(),
    cancelImageUpload: vi.fn(),
}));

/* The transport is exercised by its own tests; here only the editor's use of it matters. */
vi.mock('@/lib/imageUploads', async (importOriginal) => ({
    ...(await importOriginal<typeof ImageUploads>()),
    ...uploadMocks,
}));

const imageUploadRoute = {
    url: '/documentation/manage/images',
    method: 'post',
} as const;

const acceptedUpload = {
    id: 'upload-1',
    target: 'documentation-image',
    target_key: null,
    status: 'pending',
    error_code: null,
    created_at: null,
    poll_url: '/media/image-uploads/upload-1',
    cancel_url: '/media/image-uploads/upload-1',
    url: null,
    message: null,
    conversation: null,
};

function mountEditorWithUploads() {
    return mount(RichTextEditor, {
        props: { modelValue, imageUploadRoute },
        global: { stubs },
        attachTo: document.body,
    });
}

/** Drive the hidden file input the way a real file picker would. */
async function selectImageFile(
    wrapper: ReturnType<typeof mountEditorWithUploads>,
): Promise<void> {
    const field = wrapper.get('[data-test="rich-text-image-input"]');
    const element = field.element as HTMLInputElement;

    Object.defineProperty(element, 'files', {
        value: [new File(['bytes'], 'diagram.png', { type: 'image/png' })],
        configurable: true,
    });

    await field.trigger('change');
    await flushPromises();
    await nextTick();
}

/** Collect alt text and hand over a file, stopping before the upload resolves. */
async function startImageInsertion(
    wrapper: ReturnType<typeof mountEditorWithUploads>,
    alt = 'Approval flow diagram',
): Promise<void> {
    await wrapper.get('[data-test="rich-text-image-trigger"]').trigger('click');
    await nextTick();
    await wrapper.get('[data-test="rich-text-image-alt"]').setValue(alt);
    await wrapper.get('[data-test="rich-text-image-submit"]').trigger('submit');
    await nextTick();
}

const passthroughStub = { template: '<div><slot /></div>' };

/*
 * The registry toggle group and scroll area measure real layout through
 * ResizeObserver and pointer capture, which jsdom does not provide. The stubs
 * keep the controlled `model-value` and the per-item button so a test can still
 * assert which formats the toolbar reports as active.
 */
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
    ScrollArea: passthroughStub,
    ButtonGroup: { template: '<div role="group"><slot /></div>' },
    Field: { template: '<div role="group"><slot /></div>' },
    FieldLabel: { template: '<label><slot /></label>' },
    ToggleGroup: {
        props: ['modelValue'],
        template:
            '<div role="group" :data-active="(modelValue || []).join(\',\')"><slot /></div>',
    },
    ToggleGroupItem: {
        props: ['value', 'disabled'],
        inheritAttrs: false,
        template:
            '<button type="button" :data-value="value" :disabled="disabled || undefined" v-bind="$attrs"><slot /></button>',
    },
};

const modelValue: RichTextDocument = {
    type: 'doc',
    content: [{ type: 'paragraph' }],
};

function mountEditor() {
    return mount(RichTextEditor, {
        props: { modelValue },
        global: { stubs },
        attachTo: document.body,
    });
}

/**
 * Reaches the live Tiptap instance the component owns. `<script setup>` keeps
 * its bindings off the public instance and unwraps refs in `setupState`, so the
 * cast is what makes the editor reachable from a test.
 */
function editorOf(wrapper: ReturnType<typeof mountEditor>): Editor {
    return (wrapper.vm.$ as unknown as { setupState: { editor: Editor } })
        .setupState.editor;
}

describe('rich text editor', () => {
    it('labels every toolbar action through the generic editor translation domain', () => {
        const wrapper = mountEditor();

        for (const label of [
            'editor.action.bold',
            'editor.action.italic',
            'editor.action.bullet_list',
            'editor.action.link',
            'editor.action.undo',
            'editor.table.label',
        ]) {
            expect(wrapper.find(`[aria-label="${label}"]`).exists()).toBe(true);
        }

        expect(wrapper.html()).not.toContain('documentation.editor');
    });

    it('offers paragraph and every supported heading level as an exclusive block style', () => {
        const wrapper = mountEditor();

        expect(
            wrapper.get('[data-test="rich-text-block-trigger"]').text(),
        ).toContain('editor.action.paragraph');
        expect(
            wrapper
                .findAll(
                    '[role="radiogroup"] [role="menuitemradio"][data-test^="rich-text-block-"]',
                )
                .map((item) => item.attributes('data-test')),
        ).toEqual([
            'rich-text-block-paragraph',
            'rich-text-block-heading_1',
            'rich-text-block-heading_2',
            'rich-text-block-heading_3',
        ]);
    });

    it('reports the active formats once a command is applied', async () => {
        const wrapper = mountEditor();
        await nextTick();

        expect(
            wrapper
                .get('[aria-label="editor.group.format"]')
                .attributes('data-active'),
        ).toBe('');

        await wrapper.get('[aria-label="editor.action.bold"]').trigger('click');
        await nextTick();

        expect(
            wrapper
                .get('[aria-label="editor.group.format"]')
                .attributes('data-active'),
        ).toBe('bold');

        await wrapper
            .get('[aria-label="editor.action.bullet_list"]')
            .trigger('click');
        await nextTick();

        expect(
            wrapper
                .get('[aria-label="editor.group.block"]')
                .attributes('data-active'),
        ).toBe('bulletList');
    });

    it('disables history commands the editor cannot run', async () => {
        const wrapper = mountEditor();
        const undo = () => wrapper.get('[aria-label="editor.action.undo"]');
        const redo = () => wrapper.get('[aria-label="editor.action.redo"]');

        expect(undo().attributes()).toHaveProperty('disabled');
        expect(redo().attributes()).toHaveProperty('disabled');

        editorOf(wrapper).commands.insertContent('draft');
        await nextTick();

        expect(undo().attributes()).not.toHaveProperty('disabled');
        expect(redo().attributes()).toHaveProperty('disabled');

        await undo().trigger('click');
        await nextTick();

        expect(redo().attributes()).not.toHaveProperty('disabled');
    });

    it('offers table insertion outside a table and contextual operations inside one', async () => {
        const wrapper = mountEditor();
        await nextTick();

        expect(
            wrapper.find('[data-test="rich-text-table-insert"]').exists(),
        ).toBe(true);
        expect(
            wrapper.find('[data-test="rich-text-table-delete-row"]').exists(),
        ).toBe(false);

        await wrapper
            .get('[data-test="rich-text-table-insert"]')
            .trigger('click');
        await nextTick();

        expect(
            wrapper.find('[data-test="rich-text-table-insert"]').exists(),
        ).toBe(false);

        for (const operation of [
            'add_row_before',
            'add_row_after',
            'delete_row',
            'add_column_before',
            'add_column_after',
            'delete_column',
            'toggle_header_row',
            'delete_table',
        ]) {
            expect(
                wrapper
                    .find(`[data-test="rich-text-table-${operation}"]`)
                    .exists(),
            ).toBe(true);
        }
    });

    it('emits the updated document as JSON through v-model', async () => {
        const wrapper = mountEditor();
        await nextTick();

        await wrapper
            .get('[aria-label="editor.action.bullet_list"]')
            .trigger('click');
        await nextTick();

        const emitted = wrapper.emitted('update:modelValue');

        expect(emitted).toBeTruthy();
        expect((emitted?.at(-1)?.[0] as RichTextDocument).type).toBe('doc');
        expect(JSON.stringify(emitted?.at(-1)?.[0])).toContain('bulletList');
    });

    it('collects a link URL through a dialog instead of window.prompt', async () => {
        const prompt = vi.fn();
        vi.stubGlobal('prompt', prompt);
        const wrapper = mountEditor();
        await nextTick();

        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(false);

        await wrapper.get('[aria-label="editor.action.link"]').trigger('click');

        expect(prompt).not.toHaveBeenCalled();
        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(true);
        expect(
            wrapper
                .get('[data-test="rich-text-link-input"]')
                .attributes('value'),
        ).toBe('https://');
        vi.unstubAllGlobals();
    });

    it('applies a link to the selection and closes the dialog', async () => {
        const wrapper = mountEditor();
        await nextTick();
        const editor = editorOf(wrapper);

        editor.commands.setContent({
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [{ type: 'text', text: 'guide' }],
                },
            ],
        });
        editor.commands.selectAll();
        await nextTick();

        await wrapper.get('[aria-label="editor.action.link"]').trigger('click');
        await wrapper
            .get('[data-test="rich-text-link-input"]')
            .setValue('/internal-guide');
        await wrapper.get('[data-test="link-dialog"] form').trigger('submit');

        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(false);
        expect(editor.getAttributes('link').href).toBe('/internal-guide');
    });

    it('removes the current link when the URL is cleared', async () => {
        const wrapper = mountEditor();
        await nextTick();
        const editor = editorOf(wrapper);

        editor.commands.setContent({
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [{ type: 'text', text: 'guide' }],
                },
            ],
        });
        editor.commands.selectAll();
        editor.commands.setLink({ href: '/internal-guide' });

        await wrapper.get('[aria-label="editor.action.link"]').trigger('click');
        await wrapper.get('[data-test="rich-text-link-input"]').setValue('   ');
        await wrapper.get('[data-test="link-dialog"] form').trigger('submit');

        expect(editor.isActive('link')).toBe(false);
    });

    it('disables formatting commands when the selection does not allow them', async () => {
        const wrapper = mountEditor();
        await nextTick();
        const editor = editorOf(wrapper);
        const bold = () => wrapper.get('[data-test="rich-text-toggle-bold"]');

        expect(bold().attributes()).not.toHaveProperty('disabled');

        editor.commands.setContent({
            type: 'doc',
            content: [
                {
                    type: 'codeBlock',
                    content: [{ type: 'text', text: 'code content' }],
                },
            ],
        });
        editor.commands.selectAll();
        await nextTick();

        expect(bold().attributes()).toHaveProperty('disabled');
    });

    it('repeats every toolbar action in the editor context menu', async () => {
        const wrapper = mountEditor();
        await nextTick();

        for (const action of [
            'rich-text-context-block-trigger',
            'rich-text-context-bold',
            'rich-text-context-italic',
            'rich-text-context-bulletList',
            'rich-text-context-orderedList',
            'rich-text-context-blockquote',
            'rich-text-context-codeBlock',
            'rich-text-context-link',
            'rich-text-context-table-trigger',
            'rich-text-context-undo',
            'rich-text-context-redo',
        ]) {
            expect(wrapper.find(`[data-test="${action}"]`).exists()).toBe(true);
        }
    });

    it('keeps a shortcut it handled from reaching application-wide listeners', async () => {
        const wrapper = mountEditor();
        await nextTick();
        const onWindowKeydown = vi.fn();
        window.addEventListener('keydown', onWindowKeydown);
        const editable = editorOf(wrapper).view.dom;
        const press = (key: string) =>
            editable.dispatchEvent(
                new KeyboardEvent('keydown', {
                    key,
                    ctrlKey: true,
                    bubbles: true,
                    cancelable: true,
                }),
            );

        /*
         * Ctrl+B is the sidebar toggle as well as the bold command, so the
         * editor has to consume it; Ctrl+K belongs to the command palette and
         * must still reach the window listener behind it.
         */
        press('b');
        await nextTick();

        expect(editorOf(wrapper).isActive('bold')).toBe(true);
        expect(onWindowKeydown).not.toHaveBeenCalled();

        press('k');

        expect(onWindowKeydown).toHaveBeenCalledOnce();
        window.removeEventListener('keydown', onWindowKeydown);
    });

    it('names the Tiptap keyboard shortcut behind every context menu command that has one', () => {
        const wrapper = mountEditor();
        const keysOf = (action: string) =>
            wrapper
                .get(`[data-test="rich-text-context-${action}"]`)
                .findAll('[data-slot="kbd-group"] kbd')
                .map((key) => key.text());

        expect(keysOf('bold')).toEqual(['Ctrl', 'B']);
        expect(keysOf('bulletList')).toEqual(['Ctrl', 'Shift', '8']);
        expect(keysOf('codeBlock')).toEqual(['Ctrl', 'Alt', 'C']);
        expect(keysOf('redo')).toEqual(['Ctrl', 'Shift', 'Z']);
        expect(keysOf('block-heading_2')).toEqual(['Ctrl', 'Alt', '2']);
        expect(
            wrapper
                .get('[data-test="rich-text-context-link"]')
                .find('kbd')
                .exists(),
        ).toBe(false);
    });

    it('applies a format chosen from the context menu and reports it as active', async () => {
        const wrapper = mountEditor();
        await nextTick();
        const bulletList = () =>
            wrapper.get('[data-test="rich-text-context-bulletList"]');

        expect(bulletList().attributes('aria-checked')).toBe('false');

        await bulletList().trigger('click');
        await nextTick();

        expect(editorOf(wrapper).isActive('bulletList')).toBe(true);
        expect(bulletList().attributes('aria-checked')).toBe('true');
    });

    it('opens the link dialog from the context menu once the menu has closed', async () => {
        vi.useFakeTimers();
        const wrapper = mountEditor();
        await nextTick();

        await wrapper
            .get('[data-test="rich-text-context-link"]')
            .trigger('click');

        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(false);

        vi.runAllTimers();
        await nextTick();

        expect(wrapper.find('[data-test="link-dialog"]').exists()).toBe(true);
        vi.useRealTimers();
    });

    it('offers contextual table operations in the context menu once inside a table', async () => {
        const wrapper = mountEditor();
        await nextTick();

        expect(
            wrapper
                .find('[data-test="rich-text-context-table-insert"]')
                .exists(),
        ).toBe(true);

        await wrapper
            .get('[data-test="rich-text-context-table-insert"]')
            .trigger('click');
        await nextTick();

        expect(
            wrapper
                .find('[data-test="rich-text-context-table-insert"]')
                .exists(),
        ).toBe(false);
        expect(
            wrapper
                .find('[data-test="rich-text-context-table-delete_table"]')
                .exists(),
        ).toBe(true);
    });

    it('disables table and link commands appropriately', async () => {
        const wrapper = mountEditor();
        await nextTick();
        const link = () => wrapper.get('[aria-label="editor.action.link"]');
        const tableTrigger = () =>
            wrapper.get('[data-test="rich-text-table-trigger"]');

        expect(tableTrigger().attributes()).not.toHaveProperty('disabled');
        expect(link().attributes()).not.toHaveProperty('disabled');
    });

    describe('image uploads', () => {
        beforeEach(() => {
            vi.clearAllMocks();
            uploadMocks.startImageUpload.mockResolvedValue(acceptedUpload);
            uploadMocks.pollImageUpload.mockResolvedValue({
                ...acceptedUpload,
                status: 'ready',
                url: 'http://localhost/storage/documentation/upload-1/diagram.webp',
            });
            uploadMocks.cancelImageUpload.mockResolvedValue(null);
        });

        it('offers no image control until a consumer supplies an endpoint', () => {
            const withoutRoute = mountEditor();

            expect(
                withoutRoute
                    .find('[data-test="rich-text-image-trigger"]')
                    .exists(),
            ).toBe(false);
            expect(
                withoutRoute
                    .find('[data-test="rich-text-image-input"]')
                    .exists(),
            ).toBe(false);

            expect(
                mountEditorWithUploads()
                    .find('[data-test="rich-text-image-trigger"]')
                    .exists(),
            ).toBe(true);
        });

        it('inserts an image node carrying the published url and the collected alt text', async () => {
            const wrapper = mountEditorWithUploads();
            await nextTick();

            await startImageInsertion(wrapper);
            await selectImageFile(wrapper);

            expect(uploadMocks.startImageUpload).toHaveBeenCalledWith(
                imageUploadRoute.url,
                expect.any(File),
                expect.anything(),
            );

            const image = editorOf(wrapper)
                .getJSON()
                .content?.find((node) => node.type === 'image');

            expect(image?.attrs).toEqual({
                src: 'http://localhost/storage/documentation/upload-1/diagram.webp',
                alt: 'Approval flow diagram',
            });
        });

        it('refuses to hand over a file before alt text has been given', async () => {
            const wrapper = mountEditorWithUploads();
            await nextTick();

            await wrapper
                .get('[data-test="rich-text-image-trigger"]')
                .trigger('click');
            await nextTick();

            expect(
                wrapper
                    .get('[data-test="rich-text-image-submit"]')
                    .attributes(),
            ).toHaveProperty('disabled');
        });

        it('shows the rejection message and inserts nothing when the file is refused', async () => {
            uploadMocks.startImageUpload.mockRejectedValue(
                new ImageUploadError(422, {
                    errors: {
                        image: ['The image must be a PNG, JPEG, or WebP.'],
                    },
                }),
            );

            const wrapper = mountEditorWithUploads();
            await nextTick();

            await startImageInsertion(wrapper);
            await selectImageFile(wrapper);

            expect(
                wrapper.get('[data-test="rich-text-image-error"]').text(),
            ).toBe('The image must be a PNG, JPEG, or WebP.');
            expect(
                editorOf(wrapper)
                    .getJSON()
                    .content?.some((node) => node.type === 'image'),
            ).toBe(false);
        });

        it('reports a failed upload through the shared media error vocabulary', async () => {
            uploadMocks.pollImageUpload.mockResolvedValue({
                ...acceptedUpload,
                status: 'failed',
                error_code: 'processing_failed',
            });

            const wrapper = mountEditorWithUploads();
            await nextTick();

            await startImageInsertion(wrapper);
            await selectImageFile(wrapper);

            expect(
                wrapper.get('[data-test="rich-text-image-error"]').text(),
            ).toBe('media.upload.error.processing_failed');
        });

        it('abandons an upload still in flight when the author cancels', async () => {
            let releasePolling = (): void => {};
            uploadMocks.pollImageUpload.mockImplementation(
                () =>
                    new Promise((resolve) => {
                        releasePolling = () => resolve(null);
                    }),
            );

            const wrapper = mountEditorWithUploads();
            await nextTick();

            await startImageInsertion(wrapper);
            await selectImageFile(wrapper);

            /* Still processing: the status strip is the only progress surface. */
            expect(
                wrapper.get('[data-test="rich-text-image-status"]').text(),
            ).toContain('media.upload.status.processing');

            await wrapper
                .get('[data-test="rich-text-image-cancel"]')
                .trigger('click');
            releasePolling();
            await flushPromises();
            await nextTick();

            expect(uploadMocks.cancelImageUpload).toHaveBeenCalledWith(
                acceptedUpload.cancel_url,
            );
            expect(
                editorOf(wrapper)
                    .getJSON()
                    .content?.some((node) => node.type === 'image'),
            ).toBe(false);
        });
    });
});
