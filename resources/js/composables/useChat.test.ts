import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ImageUploadRecord } from '@/lib/imageUploads';

/*
 * Only the transport is mocked. What matters here is when `sendMessage`
 * resolves relative to the queue work behind it, which is what decides how long
 * a surface holds its composer and the navigation guard.
 */
vi.mock('@/lib/chatClient', async (importOriginal) => {
    const actual = await importOriginal<object>();

    return { ...actual, chatRequest: vi.fn() };
});

vi.mock('@/lib/imageUploads', async (importOriginal) => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        pollImageUpload: vi.fn(),
        cancelImageUpload: vi.fn(),
        activeImageUploads: vi.fn(),
    };
});

const { chatRequest } = await import('@/lib/chatClient');
const { pollImageUpload, cancelImageUpload, activeImageUploads } =
    await import('@/lib/imageUploads');
const { useChat } = await import('@/composables/useChat');

const record = (
    overrides: Partial<ImageUploadRecord> = {},
): ImageUploadRecord => ({
    id: 'upload-1',
    target: 'chat-image',
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

/** A promise held open so the in-flight window is observable. */
const deferred = <TValue>() => {
    let settle: (value: TValue) => void = () => undefined;
    const promise = new Promise<TValue>((resolve) => {
        settle = resolve;
    });

    return { promise, settle };
};

const image = (): File =>
    new File(['bytes'], 'photo.png', { type: 'image/png' });

describe('useChat image messages', () => {
    beforeEach(() => {
        vi.mocked(chatRequest).mockReset();
        vi.mocked(pollImageUpload).mockReset();
        vi.mocked(cancelImageUpload).mockReset();
        vi.mocked(activeImageUploads).mockReset();
    });

    it('resolves as soon as the transfer is accepted, not when it is published', async () => {
        const accepted = record();
        const settled = deferred<ImageUploadRecord | null>();

        vi.mocked(chatRequest).mockResolvedValue({ data: accepted });
        vi.mocked(pollImageUpload).mockReturnValue(settled.promise);

        const { state, sendMessage } = useChat();

        const outcome = await sendMessage({ body: '', image: image() });

        /*
         * The queue is still working, but the bytes are in. Anything still held
         * at this point — the composer, the navigation guard — is held for no
         * reason.
         */
        expect(outcome.accepted).toBe(true);
        expect(outcome.settled).not.toBeNull();
        expect(state.sending).toBe(false);
        expect(state.uploadProgress).toBeNull();

        /* And the watcher really is still running behind it. */
        expect(state.pendingImageUploads).toHaveLength(1);
        expect(state.pendingImageUploads[0].id).toBe('upload-1');

        settled.settle(null);
        await outcome.settled;

        expect(state.pendingImageUploads).toHaveLength(0);
    });

    it('keeps concurrent uploads from overwriting one another', async () => {
        const first = deferred<ImageUploadRecord | null>();
        const second = deferred<ImageUploadRecord | null>();

        vi.mocked(chatRequest)
            .mockResolvedValueOnce({ data: record({ id: 'upload-1' }) })
            .mockResolvedValueOnce({ data: record({ id: 'upload-2' }) });
        vi.mocked(pollImageUpload)
            .mockReturnValueOnce(first.promise)
            .mockReturnValueOnce(second.promise);

        const { state, sendMessage } = useChat();

        const one = await sendMessage({ body: '', image: image() });
        const two = await sendMessage({ body: '', image: image() });

        expect(state.pendingImageUploads.map((item) => item.id)).toEqual([
            'upload-1',
            'upload-2',
        ]);

        /* The first settling must leave the second exactly where it was. */
        first.settle(null);
        await one.settled;

        expect(state.pendingImageUploads.map((item) => item.id)).toEqual([
            'upload-2',
        ]);

        second.settle(null);
        await two.settled;

        expect(state.pendingImageUploads).toHaveLength(0);
    });

    it('cancels the upload it was given rather than whichever is first', async () => {
        const first = deferred<ImageUploadRecord | null>();
        const second = deferred<ImageUploadRecord | null>();

        vi.mocked(chatRequest)
            .mockResolvedValueOnce({ data: record({ id: 'upload-1' }) })
            .mockResolvedValueOnce({
                data: record({
                    id: 'upload-2',
                    cancel_url: '/media/image-uploads/upload-2',
                }),
            });
        vi.mocked(pollImageUpload)
            .mockReturnValueOnce(first.promise)
            .mockReturnValueOnce(second.promise);
        vi.mocked(cancelImageUpload).mockResolvedValue(
            record({ id: 'upload-2', status: 'cancelled' }),
        );

        const { state, sendMessage, cancelPendingImage } = useChat();

        const one = await sendMessage({ body: '', image: image() });
        const two = await sendMessage({ body: '', image: image() });

        await cancelPendingImage('upload-2');

        expect(cancelImageUpload).toHaveBeenCalledWith(
            '/media/image-uploads/upload-2',
        );
        expect(state.pendingImageUploads.map((item) => item.id)).toEqual([
            'upload-1',
        ]);

        first.settle(null);
        second.settle(null);
        await Promise.all([one.settled, two.settled]);
    });

    it('reports a text message as sent without any pending upload', async () => {
        const conversation = { id: 'conversation-1', last_message_at: null };
        const message = { id: 'message-1', conversation_id: 'conversation-1' };

        vi.mocked(chatRequest).mockResolvedValue({ conversation, message });

        const { state, sendMessage } = useChat();

        const outcome = await sendMessage({ body: 'hello' });

        expect(outcome.accepted).toBe(true);
        expect(outcome.message).toEqual(message);
        /* Nothing to wait for: a text message is already stored. */
        expect(outcome.settled).toBeNull();
        expect(state.pendingImageUploads).toHaveLength(0);
        expect(pollImageUpload).not.toHaveBeenCalled();
    });
});
