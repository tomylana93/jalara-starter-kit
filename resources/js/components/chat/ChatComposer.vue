<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';

/**
 * The longest body the server accepts, mirrored so the surface can stop a
 * message that would only be rejected.
 */
const MAX_LENGTH = 4000;

type Props = {
    disabled?: boolean;
    sending?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    sending: false,
});

const emit = defineEmits<{
    send: [body: string];
}>();

const { t } = useTranslations();

const body = ref('');

/* Multiline is the point, so Enter inserts a newline and only the button sends. */
const canSend = computed(
    () =>
        !props.disabled &&
        !props.sending &&
        body.value.trim() !== '' &&
        body.value.length <= MAX_LENGTH,
);

const send = (): void => {
    if (!canSend.value) {
        return;
    }

    emit('send', body.value.trim());
    body.value = '';
};
</script>

<template>
    <form class="flex items-end gap-2 border-t p-3" @submit.prevent="send">
        <label class="sr-only" for="chat-composer">
            {{ t('chat.label.composer') }}
        </label>
        <Textarea
            id="chat-composer"
            v-model="body"
            rows="2"
            class="max-h-40 min-h-10 flex-1 resize-none"
            :disabled="props.disabled"
            :placeholder="t('chat.placeholder.composer')"
            data-test="chat-composer-input"
        />
        <Button
            type="submit"
            size="sm"
            :disabled="!canSend"
            data-test="chat-send-button"
        >
            {{ t('chat.button.send') }}
        </Button>
    </form>
</template>
