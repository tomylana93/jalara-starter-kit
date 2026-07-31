import { router } from '@inertiajs/vue3';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ImageUploadField from './ImageUploadField.vue';

type PostOptions = {
    onCancelToken?: (token: { cancel: () => void }) => void;
    onCancel?: () => void;
    onProgress?: (event: {
        progress?: number;
        loaded: number;
        total?: number;
        percentage?: number;
    }) => void;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;
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

let post: ReturnType<typeof vi.spyOn>;

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

    post = vi.spyOn(router, 'post').mockImplementation(() => undefined);
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

const optionsOf = (): PostOptions => post.mock.calls[0][2] as PostOptions;

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
        const wrapper = mountField();

        await selectFile(wrapper);
        expect(wrapper.attributes('data-state')).toBe('uploading');
        expect(wrapper.find('[role="status"]').exists()).toBe(true);

        optionsOf().onProgress?.({ loaded: 40, total: 100 });
        await wrapper.vm.$nextTick();
        expect(wrapper.attributes('data-state')).toBe('uploading');
        expect(
            wrapper.get('[role="progressbar"]').attributes('aria-valuenow'),
        ).toBe('40');

        optionsOf().onProgress?.({ loaded: 100, total: 100 });
        await wrapper.vm.$nextTick();
        expect(wrapper.attributes('data-state')).toBe('processing');

        optionsOf().onSuccess?.();
        await wrapper.vm.$nextTick();
        expect(wrapper.attributes('data-state')).toBe('done');
        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });

    it('does not render passive saved or empty status text', () => {
        expect(mountField().find('[role="status"]').exists()).toBe(false);
        expect(
            mountField({ currentUrl: '/storage/logo.png' })
                .find('[role="status"]')
                .exists(),
        ).toBe(false);
    });

    it('uses Inertia percentage when the total byte count is unavailable', async () => {
        const wrapper = mountField({ testId: 'branding-logo' });

        await selectFile(wrapper);
        expect(
            wrapper.find('[data-test="branding-logo-progress"]').exists(),
        ).toBe(true);

        optionsOf().onProgress?.({
            loaded: 40,
            percentage: 40,
        });
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

        await selectFile(wrapper);
        optionsOf().onError?.({ image: 'The image must be square.' });
        await wrapper.vm.$nextTick();

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

        await selectFile(wrapper);
        optionsOf().onError?.({ image: 'Too large.' });
        await wrapper.vm.$nextTick();

        const retry = findAction(wrapper, 'common.upload.action.retry');

        expect(retry).toBeDefined();

        await retry!.trigger('click');

        expect(post).toHaveBeenCalledTimes(2);
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

        await selectFile(wrapper);

        /* The control is locked mid-flight, so a replacement follows success. */
        optionsOf().onSuccess?.();
        optionsOf().onFinish?.();
        await wrapper.vm.$nextTick();

        await selectFile(wrapper);

        expect(URL.createObjectURL).toHaveBeenCalledTimes(2);
        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:preview');
    });

    it('registers a cancel token so history movement can abort the upload', async () => {
        const wrapper = mountField();

        await selectFile(wrapper);

        expect(optionsOf().onCancelToken).toBeTypeOf('function');

        const cancel = vi.fn();
        optionsOf().onCancelToken?.({ cancel });
        optionsOf().onCancel?.();
        await wrapper.vm.$nextTick();

        expect(wrapper.attributes('data-state')).toBe('error');
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

        await selectFile(wrapper);
        optionsOf().onError?.({ image: 'Too large.' });
        await wrapper.vm.$nextTick();

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
