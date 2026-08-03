import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ImageUploadRecord, ImageUploadStatus } from './imageUploads';
import {
    cancelImageUpload,
    ImageUploadError,
    isTerminal,
    POLL_MAX_DELAY,
    POLL_MIN_DELAY,
    POLL_TIMEOUT,
    pollImageUpload,
    startImageUpload,
    uploadErrorKey,
} from './imageUploads';

const request = vi.fn();

vi.mock('@inertiajs/core', () => ({
    http: {
        getClient: () => ({
            request: (...args: unknown[]) => request(...args),
        }),
    },
}));

const record = (
    overrides: Partial<ImageUploadRecord> = {},
): ImageUploadRecord => ({
    id: 'upload-1',
    target: 'avatar',
    target_key: null,
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

/** Answer the next request with one upload record. */
const respond = (status: ImageUploadStatus, httpStatus = 200): void => {
    request.mockResolvedValueOnce({
        status: httpStatus,
        data: JSON.stringify({ data: record({ status }) }),
    });
};

beforeEach(() => {
    request.mockReset();
});

describe('image upload polling', () => {
    it('stops as soon as the upload reaches a terminal state', async () => {
        respond('processing');
        respond('ready');

        const settled = await pollImageUpload(record(), {
            wait: async () => {},
        });

        expect(settled?.status).toBe('ready');
        expect(request).toHaveBeenCalledTimes(2);
    });

    it('returns a terminal record without polling at all', async () => {
        const settled = await pollImageUpload(record({ status: 'failed' }), {
            wait: async () => {},
        });

        expect(settled?.status).toBe('failed');
        expect(request).not.toHaveBeenCalled();
    });

    it('backs off between one and five seconds', async () => {
        const delays: number[] = [];

        for (let index = 0; index < 6; index++) {
            respond('processing');
        }

        respond('ready');

        await pollImageUpload(record(), {
            wait: async (ms) => {
                delays.push(ms);
            },
        });

        expect(delays[0]).toBe(POLL_MIN_DELAY);
        expect(Math.min(...delays)).toBeGreaterThanOrEqual(POLL_MIN_DELAY);
        expect(Math.max(...delays)).toBe(POLL_MAX_DELAY);

        /* Monotonic until it saturates. */
        expect(delays[1]).toBeGreaterThan(delays[0]);
    });

    it('gives up after the timeout without failing the job', async () => {
        request.mockResolvedValue({
            status: 200,
            data: JSON.stringify({ data: record({ status: 'processing' }) }),
        });

        let clock = 0;

        const settled = await pollImageUpload(record(), {
            now: () => clock,
            wait: async () => {
                clock += 30_000;
            },
        });

        /* Null says only that this client stopped asking. */
        expect(settled).toBeNull();
        expect(clock).toBeGreaterThanOrEqual(POLL_TIMEOUT);
    });

    it('stops when the caller aborts', async () => {
        const controller = new AbortController();

        respond('processing');

        const settled = await pollImageUpload(record(), {
            signal: controller.signal,
            wait: async () => {
                controller.abort();
            },
        });

        expect(settled).toBeNull();
    });
});

describe('image upload requests', () => {
    it('sends the file as multipart and returns the accepted record', async () => {
        request.mockResolvedValueOnce({
            status: 202,
            data: JSON.stringify({ data: record() }),
        });

        const file = new File(['x'], 'logo.png', { type: 'image/png' });
        const accepted = await startImageUpload('/upload', file);

        expect(accepted.id).toBe('upload-1');

        const sent = request.mock.calls[0][0] as { data: FormData };

        expect(sent.data.get('image')).toBe(file);
    });

    it('raises a conflict carrying the upload already holding the target', async () => {
        request.mockResolvedValueOnce({
            status: 409,
            data: JSON.stringify({ data: record({ id: 'upload-existing' }) }),
        });

        const file = new File(['x'], 'logo.png', { type: 'image/png' });

        await expect(startImageUpload('/upload', file)).rejects.toSatisfy(
            (error: unknown) =>
                error instanceof ImageUploadError &&
                error.status === 409 &&
                error.conflicting?.id === 'upload-existing',
        );
    });

    it('exposes validation messages for a rejected file', async () => {
        request.mockResolvedValueOnce({
            status: 422,
            data: JSON.stringify({
                errors: { image: ['The image must be square.'] },
            }),
        });

        const file = new File(['x'], 'logo.png', { type: 'image/png' });

        await expect(startImageUpload('/upload', file)).rejects.toSatisfy(
            (error: unknown) =>
                error instanceof ImageUploadError &&
                error.validationErrors.image === 'The image must be square.',
        );
    });

    it('reports the state an upload settled in after cancellation', async () => {
        respond('cancelled');

        const settled = await cancelImageUpload(
            '/media/image-uploads/upload-1',
        );

        expect(settled?.status).toBe('cancelled');
    });
});

describe('upload outcome copy', () => {
    it('names a cancellation rather than an error', () => {
        expect(uploadErrorKey(record({ status: 'cancelled' }))).toBe(
            'media.upload.message.cancelled',
        );
    });

    it('maps each known error code to its own key', () => {
        expect(
            uploadErrorKey(
                record({ status: 'failed', error_code: 'unauthorized' }),
            ),
        ).toBe('media.upload.error.unauthorized');
    });

    it('falls back for an unrecognised code rather than leaking it', () => {
        expect(
            uploadErrorKey(
                record({ status: 'failed', error_code: 'something-new' }),
            ),
        ).toBe('media.upload.error.processing_failed');
    });

    it('knows which states an upload can still leave', () => {
        expect(isTerminal('pending')).toBe(false);
        expect(isTerminal('processing')).toBe(false);
        expect(isTerminal('ready')).toBe(true);
        expect(isTerminal('failed')).toBe(true);
        expect(isTerminal('cancelled')).toBe(true);
    });
});
