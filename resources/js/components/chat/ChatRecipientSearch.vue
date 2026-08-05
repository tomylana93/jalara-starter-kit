<script setup lang="ts">
import { ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Command,
    CommandInput,
    CommandItem,
    CommandList,
    provideCommandGroupContext,
} from '@/components/ui/command';
import { RECIPIENT_SEARCH_MINIMUM, useChat } from '@/composables/useChat';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatProfile } from '@/types';

const emit = defineEmits<{
    select: [recipient: ChatProfile];
}>();

const { t } = useTranslations();
const { searchRecipients } = useChat();

/*
 * CommandItem injects a group context, and these results sit outside a
 * CommandGroup: the server has already filtered them, and a group mounted after
 * the palette's own filter pass would hide them for good. An id-less context
 * keeps the registry item primitive usable on its own.
 */
provideCommandGroupContext({});

const term = ref('');
const results = ref<ChatProfile[]>([]);
const searching = ref(false);

let controller: AbortController | null = null;

/*
 * A term shorter than the server minimum is never sent, and each keystroke
 * cancels the request before it so a slow answer cannot overwrite a newer one.
 */
watch(term, async (value) => {
    controller?.abort();

    if (value.trim().length < RECIPIENT_SEARCH_MINIMUM) {
        results.value = [];
        searching.value = false;

        return;
    }

    controller = new AbortController();
    searching.value = true;

    try {
        results.value = await searchRecipients(value, controller.signal);
    } catch {
        results.value = [];
    } finally {
        searching.value = false;
    }
});

const select = (recipient: ChatProfile): void => {
    emit('select', recipient);
    term.value = '';
    results.value = [];
};
</script>

<template>
    <!--
        `h-auto` overrides the primitive's `h-full`: this search sits above the
        conversation list in a flex column, so a full-height root would stretch
        over the list rather than size to its own contents.
    -->
    <Command class="h-auto space-y-2 overflow-visible bg-transparent p-3">
        <label class="sr-only" for="chat-recipient-search">
            {{ t('chat.label.search') }}
        </label>
        <CommandInput
            id="chat-recipient-search"
            v-model="term"
            type="search"
            class="h-9"
            :placeholder="t('chat.placeholder.search')"
            data-test="chat-recipient-search"
        />

        <p
            v-if="
                term.trim().length > 0 &&
                term.trim().length < RECIPIENT_SEARCH_MINIMUM
            "
            class="text-xs text-muted-foreground"
            data-test="chat-search-hint"
        >
            {{ t('chat.empty.search_hint') }}
        </p>

        <p
            v-else-if="
                term.trim().length >= RECIPIENT_SEARCH_MINIMUM &&
                !searching &&
                results.length === 0
            "
            class="text-xs text-muted-foreground"
            data-test="chat-search-empty"
        >
            {{ t('chat.empty.search') }}
        </p>

        <CommandList v-if="results.length > 0" data-test="chat-search-results">
            <CommandItem
                v-for="recipient in results"
                :key="recipient.id"
                :value="recipient.name"
                class="cursor-pointer gap-2 rounded-lg px-2 py-2"
                :data-test="`chat-recipient-${recipient.id}`"
                @select="select(recipient)"
            >
                <Avatar class="size-8 shrink-0 rounded-full">
                    <AvatarImage
                        v-if="recipient.avatar"
                        :src="recipient.avatar"
                        :alt="recipient.name"
                    />
                    <AvatarFallback
                        class="bg-primary/10 text-xs font-semibold text-primary"
                    >
                        {{ getInitials(recipient.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium">
                        {{ recipient.name }}
                    </span>
                    <span
                        v-if="recipient.role"
                        class="block truncate text-xs text-muted-foreground"
                    >
                        {{ recipient.role }}
                    </span>
                </span>
            </CommandItem>
        </CommandList>
    </Command>
</template>
