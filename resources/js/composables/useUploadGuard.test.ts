import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { UPLOAD_GUARD_HEADER, useUploadGuard } from './useUploadGuard';
import type { UseUploadGuardReturn } from './useUploadGuard';

/**
 * Mount the composable inside a real component so onUnmounted actually runs.
 */
const mountGuard = () => {
    let guard: UseUploadGuardReturn | null = null;

    const wrapper = mount(
        defineComponent({
            setup() {
                guard = useUploadGuard();

                return () => h('div');
            },
        }),
    );

    return { wrapper, guard: guard as unknown as UseUploadGuardReturn };
};

afterEach(() => {
    vi.restoreAllMocks();
});

describe('upload guard', () => {
    it('starts idle', () => {
        const { guard, wrapper } = mountGuard();

        expect(guard.isUploading.value).toBe(false);
        expect(guard.progress.value).toBe(0);

        wrapper.unmount();
    });

    it('attaches a beforeunload listener only while uploading', () => {
        const add = vi.spyOn(window, 'addEventListener');
        const remove = vi.spyOn(window, 'removeEventListener');

        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();

        expect(guard.isUploading.value).toBe(true);
        expect(add).toHaveBeenCalledWith('beforeunload', expect.any(Function));

        handle.release();

        expect(guard.isUploading.value).toBe(false);
        expect(remove).toHaveBeenCalledWith(
            'beforeunload',
            expect.any(Function),
        );

        wrapper.unmount();
    });

    it('blocks inertia visits while an upload is in flight', () => {
        const listeners: Array<(event: unknown) => void> = [];

        vi.spyOn(router, 'on').mockImplementation(((
            _name: string,
            callback: (event: unknown) => void,
        ) => {
            listeners.push(callback);

            return () => undefined;
        }) as typeof router.on);

        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();
        const event = { preventDefault: vi.fn() };

        listeners.forEach((listener) => listener(event));

        expect(event.preventDefault).toHaveBeenCalled();

        handle.release();
        wrapper.unmount();
    });

    it('lets the upload request itself through', () => {
        const listeners: Array<(event: unknown) => void> = [];

        vi.spyOn(router, 'on').mockImplementation(((
            _name: string,
            callback: (event: unknown) => void,
        ) => {
            listeners.push(callback);

            return () => undefined;
        }) as typeof router.on);

        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();

        /*
         * Without this exemption the guard would cancel the very transfer it is
         * protecting, since uploads travel as Inertia visits too.
         */
        const event = {
            preventDefault: vi.fn(),
            detail: {
                visit: { headers: { [UPLOAD_GUARD_HEADER]: 'allow' } },
            },
        };

        listeners.forEach((listener) => listener(event));

        expect(event.preventDefault).not.toHaveBeenCalled();

        handle.release();
        wrapper.unmount();
    });

    it('averages progress across concurrent uploads', () => {
        const { guard, wrapper } = mountGuard();

        const first = guard.beginUpload();
        const second = guard.beginUpload();

        first.setProgress(100);
        second.setProgress(50);

        expect(guard.activeUploads.value).toBe(2);
        expect(guard.progress.value).toBe(75);

        first.release();
        second.release();
        wrapper.unmount();
    });

    it('clamps progress to the 0-100 range', () => {
        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();

        handle.setProgress(-20);
        expect(guard.progress.value).toBe(0);

        handle.setProgress(400);
        expect(guard.progress.value).toBe(100);

        handle.release();
        wrapper.unmount();
    });

    it('releasing twice does not corrupt the counter', () => {
        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();

        handle.release();
        handle.release();

        expect(guard.activeUploads.value).toBe(0);

        wrapper.unmount();
    });

    it('releases uploads owned by a component that unmounts mid-flight', () => {
        const { guard, wrapper } = mountGuard();

        guard.beginUpload();
        expect(guard.isUploading.value).toBe(true);

        wrapper.unmount();

        expect(guard.isUploading.value).toBe(false);
    });

    it('cancels in-flight uploads when history moves', () => {
        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();
        const cancel = vi.fn();

        handle.setCancel(cancel);
        window.dispatchEvent(new PopStateEvent('popstate'));

        expect(cancel).toHaveBeenCalled();

        handle.release();
        wrapper.unmount();
    });

    it('ignores history movement once no upload is running', () => {
        const { guard, wrapper } = mountGuard();
        const handle = guard.beginUpload();
        const cancel = vi.fn();

        handle.setCancel(cancel);
        handle.release();

        window.dispatchEvent(new PopStateEvent('popstate'));

        expect(cancel).not.toHaveBeenCalled();

        wrapper.unmount();
    });
});
