<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { RefreshCwIcon, UploadIcon, XIcon } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import {
    Attachment,
    AttachmentAction,
    AttachmentActions,
    AttachmentContent,
    AttachmentDescription,
    AttachmentMedia,
    AttachmentTitle,
    AttachmentTrigger,
} from '@/components/ui/attachment';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/composables/useTranslations';
import {
    UPLOAD_GUARD_HEADER,
    useUploadGuard,
} from '@/composables/useUploadGuard';

/** The lifecycle states accepted by the shadcn-vue Attachment primitive. */
export type UploadState =
    'idle' | 'uploading' | 'processing' | 'error' | 'done';

type Props = {
    /** Endpoint that accepts the multipart upload. */
    uploadUrl: string;
    /** Endpoint that clears the stored image. */
    deleteUrl: string;
    label: string;
    /** URL of the currently stored image, or null when none is stored. */
    currentUrl: string | null;
    /** Circular preview for square images, proportional preview otherwise. */
    shape?: 'circle' | 'wide';
    /** Aspect ratio used by the proportional preview. */
    ratio?: number;
    /** Fallback text shown inside the circular preview when empty. */
    fallbackText?: string;
    disabled?: boolean;
    /** Stable hook for end-to-end tests. */
    testId?: string;
};

const props = withDefaults(defineProps<Props>(), {
    shape: 'wide',
    ratio: 16 / 9,
    fallbackText: '',
    disabled: false,
    testId: undefined,
});

const { t } = useTranslations();
const { beginUpload } = useUploadGuard();

const fileInput = ref<HTMLInputElement | null>(null);
const state = ref<UploadState>(props.currentUrl ? 'done' : 'idle');
const percentage = ref(0);
const errorMessage = ref<string | undefined>(undefined);
const previewUrl = ref<string | null>(null);
const pendingFile = ref<File | null>(null);
const storedFileSize = ref<number | null>(null);

const inputId = computed(
    () => `image-upload-${props.label.toLowerCase().replace(/\W+/gu, '-')}`,
);

const isBusy = computed(
    () => state.value === 'uploading' || state.value === 'processing',
);
const showsStatus = computed(() => isBusy.value || state.value === 'error');
const fileSize = computed(
    () => pendingFile.value?.size ?? storedFileSize.value,
);
const formattedFileSize = computed(() => {
    if (fileSize.value === null) {
        return null;
    }

    if (fileSize.value < 1024) {
        return `${fileSize.value} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    const exponent = Math.min(
        Math.floor(Math.log(fileSize.value) / Math.log(1024)),
        units.length,
    );
    const value = fileSize.value / 1024 ** exponent;
    const formattedValue = value >= 10 ? value.toFixed(0) : value.toFixed(1);

    return `${formattedValue.replace(/\.0$/u, '')} ${units[exponent - 1]}`;
});
const primaryActionLabel = computed(() =>
    hasImage.value
        ? t('common.upload.action.replace')
        : t('common.upload.action.upload'),
);

/** The preview takes precedence so the new file is visible while it uploads. */
const displayUrl = computed(() => previewUrl.value ?? props.currentUrl);

const hasImage = computed(() => displayUrl.value !== null);

/*
 * An object URL is a document-lifetime resource. Every replacement and the
 * unmount itself must revoke the previous one or the blob leaks.
 */
const setPreview = (file: File | null): void => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = file ? URL.createObjectURL(file) : null;
};

const clearPreview = (): void => setPreview(null);

onBeforeUnmount(clearPreview);

/*
 * When the server confirms a new stored URL the local preview has done its job
 * and must release the blob.
 */
watch(
    () => props.currentUrl,
    async (currentUrl, _, onCleanup) => {
        clearPreview();
        pendingFile.value = null;
        storedFileSize.value = null;

        if (!isBusy.value) {
            state.value = currentUrl ? 'done' : 'idle';
        }

        if (!currentUrl || !globalThis.fetch) {
            return;
        }

        const controller = new AbortController();
        onCleanup(() => controller.abort());

        try {
            const response = await globalThis.fetch(currentUrl, {
                method: 'HEAD',
                signal: controller.signal,
            });
            const contentLengthHeader = response.headers.get('content-length');
            const contentLength = Number(contentLengthHeader);

            if (
                response.ok &&
                contentLengthHeader !== null &&
                Number.isFinite(contentLength)
            ) {
                storedFileSize.value = contentLength;
            }
        } catch {
            // Some remote storage providers do not allow cross-origin HEAD requests.
        }
    },
    { immediate: true },
);

const upload = (file: File): void => {
    pendingFile.value = file;
    setPreview(file);

    errorMessage.value = undefined;
    percentage.value = 0;
    state.value = 'uploading';

    const guard = beginUpload();

    router.post(
        props.uploadUrl,
        { image: file },
        {
            forceFormData: true,
            preserveScroll: true,
            headers: { [UPLOAD_GUARD_HEADER]: 'allow' },
            onCancelToken: (token) => guard.setCancel(() => token.cancel()),
            onCancel: () => {
                state.value = 'error';
                errorMessage.value = t('common.upload.status.cancelled');
            },
            onProgress: (event) => {
                if (!event) {
                    return;
                }

                const value =
                    event.percentage ??
                    (event.total
                        ? Math.round((event.loaded / event.total) * 100)
                        : undefined);

                if (value === undefined) {
                    return;
                }

                percentage.value = value;
                guard.setProgress(value);

                /*
                 * The bytes are on the wire but the server has not answered
                 * yet, which is a distinct state from uploading.
                 */
                if (value >= 100) {
                    state.value = 'processing';
                }
            },
            onSuccess: () => {
                state.value = 'done';
                pendingFile.value = null;
            },
            onError: (errors) => {
                state.value = 'error';
                errorMessage.value = errors.image ?? Object.values(errors)[0];
            },
            onFinish: () => {
                percentage.value = 0;
                guard.release();
            },
        },
    );
};

const onFileSelected = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        upload(file);
    }

    /*
     * Reset the control so selecting the same file again still fires a change
     * event, which matters after a failed attempt.
     */
    input.value = '';
};

const openPicker = (): void => fileInput.value?.click();

const retry = (): void => {
    if (pendingFile.value) {
        upload(pendingFile.value);

        return;
    }

    openPicker();
};

const remove = (): void => {
    errorMessage.value = undefined;

    router.delete(props.deleteUrl, {
        preserveScroll: true,
        onSuccess: () => {
            clearPreview();
            pendingFile.value = null;
            state.value = 'idle';
        },
        onError: (errors) => {
            state.value = 'error';
            errorMessage.value = Object.values(errors)[0];
        },
    });
};

const statusLabel = computed(() => {
    switch (state.value) {
        case 'uploading':
            return t('common.upload.status.uploading');
        case 'processing':
            return t('common.upload.status.processing');
        case 'error':
            return t('common.upload.status.error');
        default:
            return '';
    }
});
</script>

<template>
    <div class="grid w-full gap-2" :data-state="state" :data-test="testId">
        <Label :for="inputId">{{ label }}</Label>

        <Attachment
            orientation="vertical"
            :state="state"
            class="w-full!"
            :data-test="testId ? `${testId}-layout` : undefined"
        >
            <AttachmentMedia
                variant="image"
                :class="[
                    'relative aspect-auto! w-full! min-w-0 overflow-visible! p-0! [&_img]:aspect-auto! [&_img]:object-contain!',
                    shape === 'circle'
                        ? 'items-center! justify-center! py-4!'
                        : 'items-start! justify-start!',
                ]"
                :data-test="testId ? `${testId}-preview` : undefined"
            >
                <Avatar
                    v-if="shape === 'circle'"
                    class="size-32 rounded-full border"
                >
                    <AvatarImage
                        v-if="displayUrl"
                        :src="displayUrl"
                        :alt="label"
                    />
                    <AvatarFallback class="rounded-full">
                        {{ fallbackText || t('common.upload.empty') }}
                    </AvatarFallback>
                </Avatar>

                <div v-else class="w-full overflow-hidden">
                    <AspectRatio :ratio="ratio">
                        <img
                            v-if="displayUrl"
                            :src="displayUrl"
                            :alt="label"
                            class="size-full object-contain"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center bg-muted text-xs text-muted-foreground"
                        >
                            {{ t('common.upload.empty') }}
                        </div>
                    </AspectRatio>
                </div>

                <div
                    v-if="isBusy"
                    class="absolute inset-0 flex items-center justify-center bg-background/70"
                >
                    <Spinner />
                </div>
            </AttachmentMedia>

            <input
                :id="inputId"
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="sr-only"
                :data-test="testId ? `${testId}-input` : undefined"
                :disabled="disabled || isBusy"
                @change="onFileSelected"
            />

            <AttachmentContent
                v-if="showsStatus || formattedFileSize"
                class="grid w-full gap-2 bg-muted/30 p-3!"
                :data-test="testId ? `${testId}-content` : undefined"
            >
                <AttachmentTitle
                    v-if="showsStatus"
                    role="status"
                    class="text-xs font-normal"
                >
                    {{ statusLabel }}
                </AttachmentTitle>

                <AttachmentDescription
                    v-if="formattedFileSize"
                    :data-test="testId ? `${testId}-metadata` : undefined"
                >
                    {{ formattedFileSize }}
                </AttachmentDescription>

                <div
                    v-if="isBusy"
                    class="grid gap-1"
                    :data-test="testId ? `${testId}-progress` : undefined"
                >
                    <Progress class="h-3" :model-value="percentage" />
                    <span class="text-xs font-medium text-muted-foreground">
                        {{ percentage }}%
                    </span>
                </div>
            </AttachmentContent>

            <TooltipProvider :delay-duration="0">
                <AttachmentActions
                    :data-test="testId ? `${testId}-actions` : undefined"
                >
                    <Tooltip v-if="state === 'error'">
                        <TooltipTrigger as-child>
                            <AttachmentAction
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                :aria-label="t('common.upload.action.retry')"
                                :disabled="disabled || isBusy"
                                @click="retry"
                            >
                                <RefreshCwIcon />
                            </AttachmentAction>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ t('common.upload.action.retry') }}
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip>
                        <TooltipTrigger as-child>
                            <AttachmentAction
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                :aria-label="primaryActionLabel"
                                :disabled="disabled || isBusy"
                                @click="openPicker"
                            >
                                <UploadIcon />
                            </AttachmentAction>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ primaryActionLabel }}
                        </TooltipContent>
                    </Tooltip>

                    <Tooltip v-if="currentUrl">
                        <TooltipTrigger as-child>
                            <AttachmentAction
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                :aria-label="t('common.upload.action.remove')"
                                :disabled="disabled || isBusy"
                                @click="remove"
                            >
                                <XIcon />
                            </AttachmentAction>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ t('common.upload.action.remove') }}
                        </TooltipContent>
                    </Tooltip>
                </AttachmentActions>
            </TooltipProvider>

            <AttachmentTrigger
                type="button"
                :aria-label="primaryActionLabel"
                :disabled="disabled || isBusy"
                :class="{
                    'pointer-events-none': disabled || isBusy,
                }"
                @click="openPicker"
            />
        </Attachment>

        <InputError
            :message="errorMessage"
            :data-test="testId ? `${testId}-error` : undefined"
        />
    </div>
</template>
