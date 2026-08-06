import { http } from '@inertiajs/core';
import type { RouteDefinition } from '@/wayfinder';

/** Mirrors `App\Enums\ImageUploadStatus`. */
export type ImageUploadStatus =
    'pending' | 'processing' | 'ready' | 'failed' | 'cancelled';

/** The upload view served by `ImageUploadResource`. */
export type ImageUploadRecord = {
    id: string;
    target: string;
    target_key: string | null;
    status: ImageUploadStatus;
    error_code: string | null;
    created_at: string | null;
    poll_url: string;
    cancel_url: string;
    url: string | null;
    message: Record<string, unknown> | null;
    conversation: Record<string, unknown> | null;
};

/**
 * Raised when an upload endpoint answers with anything other than success.
 *
 * The status is kept so a rejected file (422) and a target that is already busy
 * (409) can be told apart from a generic failure.
 */
export class ImageUploadError extends Error {
    public constructor(
        public readonly status: number,
        public readonly payload: unknown,
    ) {
        super(`Image upload request failed with status ${status}`);
        this.name = 'ImageUploadError';
    }

    /** The upload already holding the target, when this is a conflict. */
    public get conflicting(): ImageUploadRecord | null {
        if (this.status !== 409) {
            return null;
        }

        return unwrap(this.payload);
    }

    /** Field-level validation messages, when the file itself was rejected. */
    public get validationErrors(): Record<string, string> {
        if (this.status !== 422 || !isRecord(this.payload)) {
            return {};
        }

        const errors = this.payload.errors;

        if (!isRecord(errors)) {
            return {};
        }

        return Object.fromEntries(
            Object.entries(errors).map(([field, messages]) => [
                field,
                Array.isArray(messages)
                    ? String(messages[0])
                    : String(messages),
            ]),
        );
    }
}

/** Polling starts responsive and eases off; the server is not in a hurry. */
export const POLL_MIN_DELAY = 1_000;

export const POLL_MAX_DELAY = 5_000;

/**
 * How long a client keeps watching before it gives up asking.
 *
 * This is a client-side budget only. Nothing is marked failed when it runs
 * out — the job is still running, and the record can be read again later.
 */
export const POLL_TIMEOUT = 10 * 60 * 1_000;

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null;

const parse = (body: string): unknown => {
    try {
        return JSON.parse(body);
    } catch {
        return null;
    }
};

/** Resource responses arrive wrapped in `data`. */
const unwrap = (payload: unknown): ImageUploadRecord | null => {
    if (!isRecord(payload)) {
        return null;
    }

    return (payload.data ?? null) as ImageUploadRecord | null;
};

/**
 * Send one standalone request to an upload or status endpoint.
 *
 * Inertia's own HTTP client is used rather than a bare fetch so the request
 * carries the same CSRF handling and interceptors as the rest of the
 * application, and so a redirect is never mistaken for a result.
 */
const request = async (
    method: 'get' | 'post' | 'delete',
    url: string,
    data?: FormData,
    signal?: AbortSignal,
    onUploadProgress?: (percentage: number) => void,
): Promise<unknown> => {
    const response = await http.getClient().request({
        method,
        url,
        data,
        signal,
        onUploadProgress: (progress) => {
            if (progress.total && onUploadProgress) {
                onUploadProgress(
                    Math.min(
                        100,
                        Math.round((progress.loaded / progress.total) * 100),
                    ),
                );
            }
        },
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const payload = parse(response.data);

    if (response.status < 200 || response.status >= 300) {
        throw new ImageUploadError(response.status, payload);
    }

    return payload;
};

/**
 * Hand a file to an upload endpoint and get back the accepted upload.
 *
 * The promise settles as soon as the bytes are accepted, which is also the
 * moment the navigation guard may be released: from here on the work happens
 * on the server and leaving the page costs nothing.
 */
export const startImageUpload = async (
    url: string,
    file: File,
    options: {
        signal?: AbortSignal;
        onProgress?: (percentage: number) => void;
        extra?: Record<string, string>;
    } = {},
): Promise<ImageUploadRecord> => {
    const body = new FormData();
    body.append('image', file);

    for (const [key, value] of Object.entries(options.extra ?? {})) {
        body.append(key, value);
    }

    const payload = await request(
        'post',
        url,
        body,
        options.signal,
        options.onProgress,
    );
    const record = unwrap(payload);

    if (record === null) {
        throw new ImageUploadError(500, payload);
    }

    return record;
};

/** Read one upload's current state. */
const readImageUpload = async (
    pollUrl: string,
    signal?: AbortSignal,
): Promise<ImageUploadRecord | null> =>
    unwrap(await request('get', pollUrl, undefined, signal));

/** Ask for an upload to be abandoned; the answer is its resulting state. */
export const cancelImageUpload = async (
    cancelUrl: string,
    signal?: AbortSignal,
): Promise<ImageUploadRecord | null> =>
    unwrap(await request('delete', cancelUrl, undefined, signal));

/**
 * Every upload the current user still has in flight.
 *
 * This is what a freshly loaded page calls to discover that it left something
 * processing before the reload.
 */
export const activeImageUploads = async (
    route: RouteDefinition<'get'>,
    signal?: AbortSignal,
): Promise<ImageUploadRecord[]> => {
    const payload = await request('get', route.url, undefined, signal);

    if (!isRecord(payload) || !Array.isArray(payload.data)) {
        return [];
    }

    return payload.data as ImageUploadRecord[];
};

export const isTerminal = (status: ImageUploadStatus): boolean =>
    status === 'ready' || status === 'failed' || status === 'cancelled';

const sleep = (ms: number): Promise<void> =>
    new Promise((resolve) => {
        setTimeout(resolve, ms);
    });

/**
 * Watch an upload until it settles, or until the client gives up waiting.
 *
 * Resolves with the terminal record, or with `null` when the budget expires
 * first. A `null` says only that this client stopped asking: the upload is
 * untouched and reading it again later is always valid.
 */
export const pollImageUpload = async (
    upload: ImageUploadRecord,
    options: {
        signal?: AbortSignal;
        onUpdate?: (record: ImageUploadRecord) => void;
        /** Injectable for tests; both default to real time. */
        now?: () => number;
        wait?: (ms: number) => Promise<void>;
    } = {},
): Promise<ImageUploadRecord | null> => {
    const now = options.now ?? (() => Date.now());
    const wait = options.wait ?? sleep;
    const startedAt = now();

    let delay = POLL_MIN_DELAY;
    let latest = upload;

    while (!isTerminal(latest.status)) {
        if (now() - startedAt >= POLL_TIMEOUT) {
            return null;
        }

        await wait(delay);

        if (options.signal?.aborted) {
            return null;
        }

        const record = await readImageUpload(latest.poll_url, options.signal);

        if (record === null) {
            return null;
        }

        latest = record;
        options.onUpdate?.(record);

        delay = Math.min(Math.round(delay * 1.5), POLL_MAX_DELAY);
    }

    return latest;
};

/**
 * The translation key describing why an upload did not finish.
 *
 * Codes come from the server and are deliberately coarse; the exception behind
 * one stays in the log.
 */
export const uploadErrorKey = (record: ImageUploadRecord): string => {
    if (record.status === 'cancelled') {
        return 'media.upload.message.cancelled';
    }

    const known = [
        'processing_failed',
        'unauthorized',
        'target_unavailable',
    ] as const;
    const code = known.find((candidate) => candidate === record.error_code);

    return `media.upload.error.${code ?? 'processing_failed'}`;
};
