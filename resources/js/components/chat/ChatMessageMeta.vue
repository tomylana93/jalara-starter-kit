<script setup lang="ts">
import { Check, CheckCheck } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

/**
 * The time and delivery status that trail a message.
 *
 * Rendered twice per bubble shape - on its own row under the text, or over the
 * image of a message that carries no text - so it lives here rather than being
 * duplicated into both branches of the bubble.
 *
 * Time and status stay a single tight group: the caller decides where the group
 * sits, and never pulls its two halves apart.
 */
type Props = {
    time: string;
    /* `none` is every incoming message: only the sender is told about reads. */
    status: 'none' | 'sent' | 'read';
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();

const { t } = useTranslations();

/*
 * The check marks carry the whole meaning, so the label is what makes the
 * status readable to a screen reader and to a hovering pointer alike.
 */
const statusLabel = computed(() =>
    props.status === 'read' ? t('chat.label.read') : t('chat.label.sent'),
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1.5 text-[0.625rem] leading-none tabular-nums select-none',
                props.class,
            )
        "
    >
        <span>{{ props.time }}</span>
        <span
            v-if="props.status !== 'none'"
            role="img"
            :aria-label="statusLabel"
            :title="statusLabel"
            class="inline-flex"
            data-test="chat-read-receipt"
        >
            <!--
                `text-current` is load-bearing: `app.css` paints every
                `svg.lucide` with the brand color, and its escape hatch matches
                `text-primary-foreground` as a whole class token - which the
                bubble never carries, because the variant applies it through
                `*:data-[slot=bubble-content]:`. Without this the check marks
                render brand-on-brand inside an outgoing bubble and disappear.
            -->
            <CheckCheck
                v-if="props.status === 'read'"
                class="size-3.5 text-current"
            />
            <Check v-else class="size-3.5 text-current" />
        </span>
    </span>
</template>
