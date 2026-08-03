import { router } from '@inertiajs/vue3';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ImageUploadRecord } from '@/lib/imageUploads';
import { ImageUploadError } from '@/lib/imageUploads';
import ImageUploadField from './ImageUploadField.vue';

/*
 * The transport is mocked, not the state machine: these tests are about what
 * the field shows as an upload moves through the queue.
 */
vi.mock('@/lib/imageUploads', async (importOriginal) => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        startImageUpload: vi.fn(),
        pollImageUpload: vi.fn(),
        cancelImageUpload: vi.fn(),
    };
});

const { startImageUpload, pollImageUpload, cancelImageUpload } =
    await import('@/lib/imageUploads');

const record = (
    overrides: Partial<ImageUploadRecord> = {},
): ImageUploadRecord => ({
    id: 'upload-1',
    target: 'branding',
    target_key: 'logo',
    status: 'pending',
    error_code: null,
    created_at: null,
    poll_url: '/media/image-uploads/upload-1',
    cancel_url: '/media/image-uploads/upload-1',
    url: null,
    message: null,
    conversation: null,
    ...overrides,
});

/** Report transfer progress the way the real client would. */
const reportProgress = (percentage: number): void => {
    const options = vi.mocked(startImageUpload).mock.calls[0][2];

    options?.onProgress?.(percentage);
};

const stubs = {
    Progress: {
        props: ['modelValue'],
        template: '<div role="progressbar" :aria-valuenow="modelValue" />',
    },
    Spinner: { template: '<span data-test="spinner" />' },
    Avatar: { template: '<div><slot /></div>' },
    AvatarImage: {
        inheritAttrs: false,
        template: '<img v-bind="$attrs" />',
    },
    AvatarFallback: { template: '<span><slot /></span>' },
    AspectRatio: { template: '<div><slot /></div>' },
    TooltipProvider: { template: '<div><slot /></div>' },
    Tooltip: { template: '<div><slot /></div>' },
    TooltipTrigger: { template: '<div><slot /></div>' },
    TooltipContent: {
        template: '<span data-test="tooltip-content"><slot /></span>',
    },
};

const props = {
    uploadUrl: '/settings/branding/assets/logo',
    deleteUrl: '/settings/branding/assets/logo',
    label: 'Logo',
    currentUrl: null,
};

const mountField = (overrides = {}) =>
    mount(ImageUploadField, {
        props: { ...props, ...overrides },
        global: { stubs },
    });

const findAction = (
    wrapper: ReturnType<typeof mountField>,
    ariaLabel: string,
) =>
    wrapper
        .findAll('[data-slot="attachment-action"]')
        .find((action) => action.attributes('aria-label') === ariaLabel);

/** Drive the file input as a user selecting a file would. */
const selectFile = async (wrapper: ReturnType<typeof mountField>) => {
    const input = wrapper.get('input[type="file"]');
    const file = new File(['x'], 'logo.png', { type: 'image/png' });

    Object.defineProperty(input.element, 'files', {
        configurable: true,
        value: [file],
    });

    await input.trigger('change');
};

beforeEach(() => {
    vi.stubGlobal('URL', {
        ...URL,
        createObjectURL: vi.fn(() => 'blob:preview'),
        revokeObjectURL: vi.fn(),
    });
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({
            ok: true,
            headers: { get: () => null },
        }),
    );

    vi.mocked(startImageUpload).mockReset();
    vi.mocked(pollImageUpload).mockReset();
    vi.mocked(cancelImageUpload).mockReset();

    /* Default: the transfer never settles, leaving the field mid-upload. */
    vi.mocked(startImageUpload).mockReturnValue(new Promise(() => {}));
    vi.mocked(pollImageUpload).mockReturnValue(new Promise(() => {}));
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('image upload field', () => {
    it('starts idle when nothing is stored', () => {
        expect(mountField().attributes('data-state')).toBe('idle');
    });

    it('starts done when an image is already stored', () => {
        expect(
            mountField({ currentUrl: '/storage/logo.png' }).attributes(
                'data-state',
            ),
        ).toBe('done');
    });

    it('composes a full-width vertical attachment tile', () => {
        const wrapper = mountField({ testId: 'branding-logo' });
        const attachment = wrapper.get('[data-test="branding-logo-layout"]');

        expect(wrapper.classes()).toContain('w-full');
        expect(attachment.attributes('data-orientation')).toBe('vertical');
        expect(attachment.classes()).toContain('w-full!');
        expect(
            wrapper.get('[data-test="branding-logo-preview"]').classes(),
        ).toContain('aspect-auto!');
        expect(
            wrapper.get('[data-test="branding-logo-preview"]').classes(),
        ).toContain('items-start!');
        expect(
            wrapper.get('[data-test="branding-logo-preview"] > div').classes(),
        ).toContain('w-full');
        expect(
            attachment.element.contains(
                wrapper.get('[data-test="branding-logo-actions"]').element,
            ),
        ).toBe(true);
        expect(
            wrapper.find('[data-test="branding-logo-content"]').exists(),
        ).toBe(false);
    });

    it('centers circular media within the attachment tile', () => {
        const preview = mountField({
            shape: 'circle',
            testId: 'profile-avatar',
        }).get('[data-test="profile-avatar-preview"]');

        expect(preview.classes()).toContain('items-center!');
        expect(preview.classes()).toContain('justify-center!');
        expect(preview.classes()).not.toContain('items-start!');
    });

    it('shows the empty label for an icon without a fallback', () => {
        expect(mountField({ shape: 'circle' }).text()).toContain(
            'common.upload.empty',
        );
        expect(
            mountField({ shape: 'circle', fallbackText: 'PA' }).text(),
        ).not.toContain('common.upload.empty');
    });

    it('shows only the selected file size as metadata', async () => {
        const wrapper = mountField({ testId: 'branding-logo' });

        await selectFile(wrapper);

        expect(wrapper.get('[data-test="branding-logo-metadata"]').text()).toBe(
            '1 B',
        );
    });

    it('loads the stored file size without rendering a passive status', async () => {
        const fetch = vi.mocked(globalThis.fetch);
        fetch.mockResolvedValueOnce(
            new Response(null, {
                headers: { 'content-length': '2048' },
            }),
        );

        const wrapper = mountField({
            currentUrl: '/storage/logo.png',
            testId: 'branding-logo',
        });
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith(
            '/storage/logo.png',
            expect.objectContaining({ method: 'HEAD' }),
        );
        expect(wrapper.get('[data-test="branding-logo-metadata"]').text()).toBe(
            '2 KB',
        );
        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });

    it('moves through uploading, processing and done', async () => {
        /* Held open so the transfer stage is observable before it settles. */
        let accept: (value: ImageUploadRecord) => void = () => {};
        vi.mocked(startImageUpload).mockReturnValue(
            new Promise((resolve) => {
                accept = resolve;
            }),
        );
        vi.mocked(pollImageUpload).mockResolvedValue(
            record({ status: 'ready', url: '/storage/new-logo.webp' }),
        );

        const wrapper = mountField();

        await selectFile(wrapper);
        expect(wrapper.attributes('data-state')).toBe('uploading');
        expect(wrapper.find('[role="status"]').exists()).toBe(true);

        reportProgress(40);
        await wrapper.vm.$nextTick();
        expect(wrapper.attributes('data-state')).toBe('uploading');
        expect(
            wrapper.get('[role="progressbar"]').attributes('aria-valuenow'),
        ).toBe('40');

        accept(record());
        await flushPromises();

        /* Accepted by the server: the wait is now on the queue, not the wire. */
        expect(wrapper.attributes('data-state')).toBe('done');
        expect(wrapper.find('[role="status"]').exists()).toBe(false);
        expect(wrapper.get('img').attributes('src')).toBe(
            '/storage/new-logo.webp',
        );
    });

    it('shows the queue state while an accepted upload is processed', async () => {
        vi.mocked(startImageUpload).mockResolvedValue(record());

        const wrapper = mountField();

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('processing');
        expect(wrapper.text()).toContain('media.upload.status.pending');

        /* Queue work has no measurable percentage, so no progress bar. */
        expect(wrapper.find('[role="progressbar"]').exists()).toBe(false);
    });

    it('keeps the stored image while a replacement is processing', async () => {
        vi.mocked(startImageUpload).mockResolvedValue(record());

        const wrapper = mountField({ currentUrl: '/storage/logo.png' });

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('processing');
        /* The local preview stands in, and the stored image is untouched. */
        expect(wrapper.get('img').attributes('src')).toBe('blob:preview');
    });

    it('restores the stored image when processing fails', async () => {
        vi.mocked(startImageUpload).mockResolvedValue(record());
        vi.mocked(pollImageUpload).mockResolvedValue(
            record({ status: 'failed', error_code: 'processing_failed' }),
        );

        const wrapper = mountField({ currentUrl: '/storage/logo.png' });

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('error');
        expect(wrapper.text()).toContain(
            'media.upload.error.processing_failed',
        );
    });

    it('offers to check again when the client stops waiting', async () => {
        vi.mocked(startImageUpload).mockResolvedValue(record());
        vi.mocked(pollImageUpload).mockResolvedValue(null);

        const wrapper = mountField();

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('error');
        expect(wrapper.text()).toContain('media.upload.message.timed_out');
        expect(
            findAction(wrapper, 'media.upload.button.check_again'),
        ).toBeDefined();
    });

    it('follows the upload already holding the target on a conflict', async () => {
        const existing = record({ id: 'upload-existing' });

        vi.mocked(startImageUpload).mockRejectedValue(
            new ImageUploadError(409, { data: existing }),
        );

        const wrapper = mountField();

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.text()).toContain('media.upload.message.conflict');
        expect(vi.mocked(pollImageUpload).mock.calls[0][0]).toMatchObject({
            id: 'upload-existing',
        });
    });

    it('does not follow a conflict it was given no way to watch', async () => {
        /* What another administrator's upload looks like from here. */
        vi.mocked(startImageUpload).mockRejectedValue(
            new ImageUploadError(409, {
                message: 'media.upload.message.conflict_other_owner',
            }),
        );

        const wrapper = mountField();

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('error');
        expect(wrapper.text()).toContain(
            'media.upload.message.conflict_other_owner',
        );

        /* Polling a stranger's upload would only ever answer 403. */
        expect(pollImageUpload).not.toHaveBeenCalled();
        expect(
            findAction(wrapper, 'media.upload.button.cancel'),
        ).toBeUndefined();
    });

    it('resumes an upload handed back after a reload', async () => {
        vi.mocked(pollImageUpload).mockResolvedValue(
            record({ status: 'ready', url: '/storage/resumed.webp' }),
        );

        const wrapper = mountField({
            resume: record({ status: 'processing' }),
        });

        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('done');
        expect(wrapper.get('img').attributes('src')).toBe(
            '/storage/resumed.webp',
        );
    });

    it('cancels an upload that has not been published yet', async () => {
        vi.mocked(startImageUpload).mockResolvedValue(record());
        vi.mocked(cancelImageUpload).mockResolvedValue(
            record({ status: 'cancelled' }),
        );

        const wrapper = mountField({ currentUrl: '/storage/logo.png' });

        await selectFile(wrapper);
        await flushPromises();

        await findAction(wrapper, 'media.upload.button.cancel')!.trigger(
            'click',
        );
        await flushPromises();

        expect(cancelImageUpload).toHaveBeenCalledWith(
            '/media/image-uploads/upload-1',
        );
        /* Cancelling never discards what was already stored. */
        expect(wrapper.attributes('data-state')).toBe('done');
        expect(wrapper.get('img').attributes('src')).toBe('/storage/logo.png');
    });

    it('does not render passive saved or empty status text', () => {
        expect(mountField().find('[role="status"]').exists()).toBe(false);
        expect(
            mountField({ currentUrl: '/storage/logo.png' })
                .find('[role="status"]')
                .exists(),
        ).toBe(false);
    });

    it('reports transfer progress as a percentage', async () => {
        const wrapper = mountField({ testId: 'branding-logo' });

        await selectFile(wrapper);
        expect(
            wrapper.find('[data-test="branding-logo-progress"]').exists(),
        ).toBe(true);

        reportProgress(40);
        await wrapper.vm.$nextTick();

        expect(
            wrapper.get('[role="progressbar"]').attributes('aria-valuenow'),
        ).toBe('40');
        expect(wrapper.text()).toContain('40%');
    });

    it('shows a spinner and shimmers the status while busy', async () => {
        const wrapper = mountField();

        await selectFile(wrapper);

        expect(wrapper.find('[data-test="spinner"]').exists()).toBe(true);
        expect(wrapper.get('[role="status"]').classes()).toContain(
            'group-data-[state=uploading]/attachment:shimmer',
        );
    });

    it('enters the error state and surfaces the validation message', async () => {
        const wrapper = mountField({ testId: 'branding-logo' });

        vi.mocked(startImageUpload).mockRejectedValue(
            new ImageUploadError(422, {
                errors: { image: ['The image must be square.'] },
            }),
        );

        await selectFile(wrapper);
        await flushPromises();

        expect(wrapper.attributes('data-state')).toBe('error');
        expect(wrapper.text()).toContain('The image must be square.');
        expect(
            wrapper
                .get('[data-test="branding-logo-layout"]')
                .element.contains(
                    wrapper.get('[data-test="branding-logo-error"]').element,
                ),
        ).toBe(false);
    });

    it('offers a retry only after a failure and resends the same file', async () => {
        const wrapper = mountField();

        expect(
            findAction(wrapper, 'common.upload.action.retry'),
        ).toBeUndefined();

        vi.mocked(startImageUpload).mockRejectedValue(
            new ImageUploadError(422, { errors: { image: ['Too large.'] } }),
        );

        await selectFile(wrapper);
        await flushPromises();

        const retry = findAction(wrapper, 'common.upload.action.retry');

        expect(retry).toBeDefined();

        vi.mocked(startImageUpload).mockReturnValue(new Promise(() => {}));
        await retry!.trigger('click');

        expect(startImageUpload).toHaveBeenCalledTimes(2);
        expect(wrapper.attributes('data-state')).toBe('uploading');
    });

    it('disables the controls while an upload is in flight', async () => {
        const wrapper = mountField();

        await selectFile(wrapper);

        expect(
            wrapper.get('input[type="file"]').attributes('disabled'),
        ).toBeDefined();

        wrapper
            .findAll('button')
            .forEach((button) =>
                expect(button.attributes('disabled')).toBeDefined(),
            );
    });

    it('previews the selected file and revokes the object url on unmount', async () => {
        const wrapper = mountField();

        await selectFile(wrapper);

        expect(URL.createObjectURL).toHaveBeenCalledTimes(1);
        expect(wrapper.get('img').attributes('src')).toBe('blob:preview');

        wrapper.unmount();

        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:preview');
    });

    it('revokes the previous object url when another file is chosen', async () => {
        const wrapper = mountField();

        vi.mocked(startImageUpload).mockResolvedValue(record());
        vi.mocked(pollImageUpload).mockResolvedValue(
            record({ status: 'ready', url: '/storage/new.webp' }),
        );

        await selectFile(wrapper);

        /* The control is locked mid-flight, so a replacement follows success. */
        await flushPromises();

        await selectFile(wrapper);

        expect(URL.createObjectURL).toHaveBeenCalledTimes(2);
        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:preview');
    });

    it('hands the guard an abort signal for the transfer', async () => {
        const wrapper = mountField();

        await selectFile(wrapper);

        const signal = vi.mocked(startImageUpload).mock.calls[0][2]?.signal;

        expect(signal).toBeInstanceOf(AbortSignal);
        expect(signal?.aborted).toBe(false);
    });

    it('offers removal only when an image is stored', async () => {
        const remove = vi
            .spyOn(router, 'delete')
            .mockImplementation(() => undefined);

        expect(
            findAction(mountField(), 'common.upload.action.remove'),
        ).toBeUndefined();

        const wrapper = mountField({
            currentUrl: '/storage/logo.png',
            fallbackText: 'PA',
            shape: 'circle',
        });
        const button = findAction(wrapper, 'common.upload.action.remove');

        await button!.trigger('click');

        expect(remove).toHaveBeenCalledWith(
            props.deleteUrl,
            expect.objectContaining({ preserveScroll: true }),
        );

        const options = remove.mock.calls[0][1] as {
            onSuccess?: () => void;
        };

        options.onSuccess?.();
        await wrapper.vm.$nextTick();

        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.text()).toContain('PA');
    });

    it('keeps the stored image when removal fails', async () => {
        const remove = vi
            .spyOn(router, 'delete')
            .mockImplementation(() => undefined);
        const wrapper = mountField({ currentUrl: '/storage/logo.png' });

        await findAction(wrapper, 'common.upload.action.remove')!.trigger(
            'click',
        );

        const options = remove.mock.calls[0][1] as {
            onError?: (errors: Record<string, string>) => void;
        };

        options.onError?.({ image: 'Unable to remove image.' });
        await wrapper.vm.$nextTick();

        expect(wrapper.find('img').exists()).toBe(true);
        expect(wrapper.text()).toContain('Unable to remove image.');
    });

    it('opens the native picker from the upload button', async () => {
        const wrapper = mountField();
        const input = wrapper.get('input[type="file"]');
        const click = vi.spyOn(input.element as HTMLInputElement, 'click');

        const button = findAction(wrapper, 'common.upload.action.upload');

        await button!.trigger('click');

        expect(click).toHaveBeenCalled();
    });

    it('labels the action replace once an image exists', () => {
        expect(
            findAction(mountField(), 'common.upload.action.upload'),
        ).toBeDefined();
        expect(
            findAction(
                mountField({ currentUrl: '/storage/logo.png' }),
                'common.upload.action.replace',
            ),
        ).toBeDefined();
    });

    it('describes each icon action with a tooltip', async () => {
        const wrapper = mountField({ currentUrl: '/storage/logo.png' });

        expect(
            wrapper
                .findAll('[data-test="tooltip-content"]')
                .map((tooltip) => tooltip.text()),
        ).toEqual([
            'common.upload.action.replace',
            'common.upload.action.remove',
        ]);

        vi.mocked(startImageUpload).mockRejectedValue(
            new ImageUploadError(422, { errors: { image: ['Too large.'] } }),
        );

        await selectFile(wrapper);
        await flushPromises();

        expect(
            wrapper
                .findAll('[data-test="tooltip-content"]')
                .map((tooltip) => tooltip.text()),
        ).toEqual([
            'common.upload.action.retry',
            'common.upload.action.replace',
            'common.upload.action.remove',
        ]);
    });
});
