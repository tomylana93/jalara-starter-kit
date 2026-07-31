<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import { BookOpen, Navigation } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
    provideCommandGroupContext,
} from '@/components/ui/command';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useTranslations } from '@/composables/useTranslations';
import { search, show } from '@/routes/documentation';

type SearchResult = {
    id: string;
    title: string;
    slug: string;
    category: string;
    excerpt: string;
};
const open = ref(false);
const query = ref('');
const results = ref<SearchResult[]>([]);
const { commandItems } = useAppNavigation();
const { t } = useTranslations();
/*
 * CommandItem requires a group context to inject. The documentation results sit
 * outside a CommandGroup, so an id-less context is provided here for them; the
 * navigation group still shadows it for its own items.
 */
provideCommandGroupContext({});
/*
 * The documentation endpoint returns plain JSON rather than an Inertia page, so
 * the standalone HTTP helper is used instead of a router visit. It owns the
 * abort controller, which `cancel()` uses to drop a superseded request.
 */
const http = useHttp<{ query: string }, { data: SearchResult[] }>({
    query: '',
});
let timeout: ReturnType<typeof setTimeout> | undefined;
function isEditable(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        (target instanceof HTMLElement && target.isContentEditable)
    );
}
function openPalette(): void {
    open.value = true;
}
function onKeydown(event: KeyboardEvent): void {
    if (
        (event.ctrlKey || event.metaKey) &&
        event.key.toLowerCase() === 'k' &&
        !isEditable(event.target)
    ) {
        event.preventDefault();
        openPalette();
    }
}
function visit(href: Parameters<typeof router.visit>[0]): void {
    open.value = false;
    router.visit(href);
}
watch(query, (value) => {
    clearTimeout(timeout);
    http.cancel();
    const normalized = value.trim();

    if (normalized.length < 2) {
        results.value = [];

        return;
    }

    timeout = setTimeout(() => {
        http.query = normalized;
        /* A cancelled request rejects, which is the expected path here. */
        void http
            .submit(search(), {
                onSuccess: (response) => {
                    results.value = response.data;
                },
            })
            .catch(() => undefined);
    }, 250);
});
watch(open, (value) => {
    if (!value) {
        query.value = '';
        results.value = [];
    }
});
onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('open-global-search', openPalette);
});
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('open-global-search', openPalette);
    clearTimeout(timeout);
    http.cancel();
});
</script>

<template>
    <CommandDialog
        v-model:open="open"
        :title="t('documentation.search.title')"
        :description="t('documentation.search.description')"
    >
        <CommandInput
            v-model="query"
            :placeholder="t('documentation.search.placeholder')"
        />
        <!-- CommandList owns the scroll area; nesting another one collapses it. -->
        <CommandList>
            <!--
                Documentation results arrive after the command filter has already
                run, so they stay outside its count. The empty message therefore
                follows the remote results rather than the filter alone.
            -->
            <CommandEmpty v-if="!results.length">{{
                t('documentation.search.empty')
            }}</CommandEmpty>
            <CommandGroup :heading="t('documentation.search.group.navigation')">
                <CommandItem
                    v-for="item in commandItems"
                    :key="item.title"
                    :value="item.title"
                    @select="visit(item.href)"
                >
                    <Navigation /><span>{{ item.title }}</span>
                </CommandItem>
            </CommandGroup>
            <template v-if="results.length">
                <CommandSeparator />
                <!--
                    These results are filtered by the server and mount after the
                    palette has run its own filter pass, so CommandGroup would
                    hide them for good. Only the heading is rendered directly;
                    the item primitive is still the registry one.
                -->
                <div
                    data-slot="command-group"
                    class="overflow-hidden p-1 text-foreground"
                >
                    <div
                        class="px-2 py-1.5 text-xs font-medium text-muted-foreground"
                    >
                        {{ t('documentation.search.group.documentation') }}
                    </div>
                    <CommandItem
                        v-for="result in results"
                        :key="result.id"
                        :value="`${result.title} ${result.category} ${result.excerpt}`"
                        @select="visit(show(result.slug))"
                    >
                        <BookOpen />
                        <span class="min-w-0"
                            ><span class="block truncate">{{
                                result.title
                            }}</span
                            ><span
                                class="block truncate text-xs text-muted-foreground"
                                >{{ result.category }} ·
                                {{ result.excerpt }}</span
                            ></span
                        >
                    </CommandItem>
                </div>
            </template>
        </CommandList>
    </CommandDialog>
</template>
