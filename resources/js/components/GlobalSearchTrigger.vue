<script setup lang="ts">
import { Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Kbd, KbdGroup } from '@/components/ui/kbd';
import { useTranslations } from '@/composables/useTranslations';
import { isApplePlatform } from '@/lib/utils';

const { t } = useTranslations();
/* Mirrors the Ctrl/Cmd pair the palette itself listens for. */
const shortcutKeys = isApplePlatform() ? ['⌘', 'K'] : ['Ctrl', 'K'];

const openGlobalSearch = (): void => {
    window.dispatchEvent(new CustomEvent('open-global-search'));
};
</script>

<template>
    <!--
        The desktop half of the search entry point: an input-shaped button that
        advertises the shortcut. Narrower viewports keep the icon button each
        header renders beside this one.
    -->
    <Button
        variant="outline"
        :aria-label="t('navigation.menu.search')"
        data-test="global-search-trigger-desktop"
        class="hidden h-9 w-64 justify-start gap-2 bg-background px-3 font-normal text-muted-foreground lg:inline-flex"
        @click="openGlobalSearch"
    >
        <Search />
        <span class="truncate">{{ t('navigation.menu.search_label') }}</span>
        <KbdGroup class="ml-auto shrink-0">
            <Kbd v-for="key in shortcutKeys" :key="key">{{ key }}</Kbd>
        </KbdGroup>
    </Button>
</template>
