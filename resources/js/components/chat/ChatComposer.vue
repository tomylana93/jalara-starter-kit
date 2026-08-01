<script setup lang="ts">
import { ArrowUp, Plus, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
    Attachment,
    AttachmentAction,
    AttachmentActions,
    AttachmentContent,
    AttachmentMedia,
    AttachmentTitle,
} from '@/components/ui/attachment';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { Progress } from '@/components/ui/progress';
import { useTranslations } from '@/composables/useTranslations';

const MAX_LENGTH = 4000;

type Props = {
    modelValue: string;
    disabled?: boolean;
    sending?: boolean;
    imageUploadsEnabled?: boolean;
    uploadProgress?: number | null;
};

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    sending: false,
    imageUploadsEnabled: true,
    uploadProgress: null,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    send: [
        payload: { body: string; image: File | null },
        complete: (succeeded: boolean) => void,
    ];
}>();

const { t } = useTranslations();
const fileInput = ref<HTMLInputElement | null>(null);
const image = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const notice = ref<string | null>(null);

const canSend = computed(
    () =>
        !props.disabled &&
        !props.sending &&
        (props.modelValue.trim() !== '' || image.value !== null) &&
        props.modelValue.length <= MAX_LENGTH,
);

const revokePreview = (): void => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

const removeImage = (): void => {
    revokePreview();
    image.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const selectImage = (event: Event): void => {
    const selected = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (!selected) {
        return;
    }

    revokePreview();
    image.value = selected;
    previewUrl.value = URL.createObjectURL(selected);
    notice.value = null;
};

const send = (): void => {
    if (!canSend.value) {
        return;
    }

    emit(
        'send',
        { body: props.modelValue.trim(), image: image.value },
        (succeeded) => {
            if (!succeeded) {
                return;
            }

            emit('update:modelValue', '');
            removeImage();
        },
    );
};

watch(
    () => props.imageUploadsEnabled,
    (enabled) => {
        if (!enabled && image.value) {
            removeImage();
            notice.value = t('chat.message.image_removed_disabled');
        }
    },
);

onBeforeUnmount(revokePreview);
</script>

<template>
    <form class="border-t p-3" @submit.prevent="send">
        <Attachment
            v-if="image && previewUrl"
            size="sm"
            :state="props.sending ? 'uploading' : 'idle'"
            class="mb-2 w-full"
            data-test="chat-image-draft"
        >
            <AttachmentMedia variant="image">
                <img :src="previewUrl" alt="" class="size-full object-cover" />
            </AttachmentMedia>
            <AttachmentContent class="min-w-0 flex-1">
                <AttachmentTitle>
                    {{
                        props.sending
                            ? t('chat.label.uploading')
                            : t('chat.label.image')
                    }}
                </AttachmentTitle>
                <div v-if="props.sending" class="mt-1 flex items-center gap-2">
                    <Progress
                        class="h-1.5 flex-1"
                        :model-value="props.uploadProgress ?? 0"
                    />
                    <span class="text-xs text-muted-foreground">
                        {{ props.uploadProgress ?? 0 }}%
                    </span>
                </div>
            </AttachmentContent>
            <AttachmentActions v-if="!props.sending">
                <AttachmentAction
                    type="button"
                    size="icon-xs"
                    variant="ghost"
                    :aria-label="t('chat.button.remove_image')"
                    @click="removeImage"
                >
                    <X />
                </AttachmentAction>
            </AttachmentActions>
        </Attachment>

        <p
            v-if="notice"
            class="mb-2 text-xs text-muted-foreground"
            role="status"
        >
            {{ notice }}
        </p>

        <input
            ref="fileInput"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            class="sr-only"
            :disabled="
                props.disabled || props.sending || !props.imageUploadsEnabled
            "
            data-test="chat-image-input"
            @change="selectImage"
        />

        <InputGroup>
            <label class="sr-only" for="chat-composer">
                {{ t('chat.label.composer') }}
            </label>
            <InputGroupTextarea
                id="chat-composer"
                :model-value="props.modelValue"
                rows="2"
                class="max-h-40 min-h-16"
                :disabled="props.disabled"
                :placeholder="t('chat.placeholder.composer')"
                data-test="chat-composer-input"
                @update:model-value="emit('update:modelValue', String($event))"
            />
            <InputGroupAddon align="block-end" class="justify-between">
                <InputGroupButton
                    type="button"
                    size="icon-xs"
                    variant="ghost"
                    :aria-label="t('chat.button.add_image')"
                    :disabled="
                        props.disabled ||
                        props.sending ||
                        !props.imageUploadsEnabled
                    "
                    data-test="chat-image-picker"
                    @click="fileInput?.click()"
                >
                    <Plus />
                </InputGroupButton>
                <InputGroupButton
                    type="submit"
                    size="icon-xs"
                    class="rounded-full"
                    :aria-label="t('chat.button.send')"
                    :disabled="!canSend"
                    data-test="chat-send-button"
                >
                    <ArrowUp />
                </InputGroupButton>
            </InputGroupAddon>
        </InputGroup>
    </form>
</template>
