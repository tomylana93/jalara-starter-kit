import type { ComputedRef } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { ImageUploadRecord } from '@/lib/imageUploads';
import { activeImageUploads } from '@/lib/imageUploads';
import { index } from '@/routes/media/image-uploads';

export type UseResumableUploadsReturn = {
    uploads: ComputedRef<ImageUploadRecord[]>;
    /** The in-flight upload for one target, if the user left one behind. */
    find: (
        target: string,
        targetKey?: string | null,
    ) => ComputedRef<ImageUploadRecord | null>;
};

/**
 * Recovers uploads that were still processing when the page was last left.
 *
 * A queued upload outlives the page that started it, so without this a reload
 * would show an unchanged image and no sign that anything was happening. One
 * request per page covers every field on it.
 */
export function useResumableUploads(): UseResumableUploadsReturn {
    const uploads = ref<ImageUploadRecord[]>([]);

    onMounted(async () => {
        try {
            uploads.value = await activeImageUploads(index());
        } catch {
            /* Recovery is a convenience; the page is perfectly usable without it. */
        }
    });

    const find = (
        target: string,
        targetKey: string | null = null,
    ): ComputedRef<ImageUploadRecord | null> =>
        computed(
            () =>
                uploads.value.find(
                    (upload) =>
                        upload.target === target &&
                        upload.target_key === targetKey,
                ) ?? null,
        );

    return { uploads: computed(() => uploads.value), find };
}
