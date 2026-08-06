import { router } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed, onUnmounted, reactive } from 'vue';

export type UploadHandle = {
    /** Report upload progress as a percentage between 0 and 100. */
    setProgress: (percentage: number) => void;
    /**
     * Register how to abort this upload. History movement cannot be blocked, so
     * the guard needs a way to abandon in-flight requests.
     */
    setCancel: (cancel: () => void) => void;
    /** Release the guard. Safe to call more than once. */
    release: () => void;
};

export type UseUploadGuardReturn = {
    isUploading: ComputedRef<boolean>;
    activeUploads: ComputedRef<number>;
    /** Aggregate progress across every in-flight upload, 0-100. */
    progress: ComputedRef<number>;
    beginUpload: () => UploadHandle;
};

/**
 * Marks a visit as an upload the guard must not block. Requests carrying this
 * header pass through; every other visit is refused while an upload runs.
 */
export const UPLOAD_GUARD_HEADER = 'X-Upload-Guard';

type GuardState = {
    uploads: Map<number, number>;
    nextId: number;
};

/*
 * One shared guard for the whole application: uploads started on the branding
 * page and the profile page must lock the same interface, so the counter and
 * its listeners cannot live per-component.
 */
const state = reactive<GuardState>({
    uploads: new Map(),
    nextId: 0,
});

let removeBeforeUnload: (() => void) | null = null;
let removeInertiaBefore: (() => void) | null = null;
let removePopState: (() => void) | null = null;

/** Cancels every in-flight upload, used when history movement cannot be stopped. */
const cancelCallbacks = new Map<number, () => void>();

const isActive = (): boolean => state.uploads.size > 0;

const warnBeforeUnload = (event: BeforeUnloadEvent): void => {
    /*
     * The browser owns the wording of this dialog. Assigning returnValue is
     * what actually makes it appear across engines.
     */
    event.preventDefault();
    event.returnValue = '';
};

const handlePopState = (): void => {
    if (!isActive()) {
        return;
    }

    /*
     * A popstate has already happened by the time it is observable, so it
     * cannot be reliably cancelled. The honest response is to abandon the
     * uploads rather than leave them looking as if they are still running.
     */
    cancelCallbacks.forEach((cancel) => cancel());
};

const attachListeners = (): void => {
    if (typeof window === 'undefined' || removeBeforeUnload) {
        return;
    }

    window.addEventListener('beforeunload', warnBeforeUnload);
    removeBeforeUnload = () =>
        window.removeEventListener('beforeunload', warnBeforeUnload);

    window.addEventListener('popstate', handlePopState);
    removePopState = () =>
        window.removeEventListener('popstate', handlePopState);

    removeInertiaBefore = router.on('before', (event) => {
        if (!isActive()) {
            return;
        }

        /*
         * The uploads themselves travel as Inertia visits, so the guard has to
         * let its own requests through or it would cancel the very transfer it
         * is protecting.
         */
        const headers = event.detail?.visit?.headers as
            Record<string, string> | undefined;

        if (headers && UPLOAD_GUARD_HEADER in headers) {
            return;
        }

        event.preventDefault();
    });
};

const detachListeners = (): void => {
    removeBeforeUnload?.();
    removeBeforeUnload = null;

    removePopState?.();
    removePopState = null;

    removeInertiaBefore?.();
    removeInertiaBefore = null;
};

export function useUploadGuard(): UseUploadGuardReturn {
    const owned = new Set<number>();

    const release = (id: number): void => {
        if (!state.uploads.has(id)) {
            return;
        }

        state.uploads.delete(id);
        cancelCallbacks.delete(id);
        owned.delete(id);

        if (!isActive()) {
            detachListeners();
        }
    };

    const beginUpload = (): UploadHandle => {
        const id = state.nextId++;

        state.uploads.set(id, 0);
        owned.add(id);
        attachListeners();

        return {
            setProgress: (percentage: number) => {
                if (!state.uploads.has(id)) {
                    return;
                }

                state.uploads.set(id, Math.min(100, Math.max(0, percentage)));
            },
            setCancel: (cancel: () => void) => {
                cancelCallbacks.set(id, cancel);
            },
            release: () => release(id),
        };
    };

    /*
     * A component unmounting mid-upload must not leave the whole application
     * locked behind a guard nothing can release.
     */
    onUnmounted(() => {
        owned.forEach((id) => release(id));
    });

    return {
        isUploading: computed(() => state.uploads.size > 0),
        activeUploads: computed(() => state.uploads.size),
        progress: computed(() => {
            if (state.uploads.size === 0) {
                return 0;
            }

            const total = [...state.uploads.values()].reduce(
                (sum, value) => sum + value,
                0,
            );

            return Math.round(total / state.uploads.size);
        }),
        beginUpload,
    };
}
