<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';

const { branding } = useBranding();
const page = usePage();

/* Release Please owns the shared value; the footer only labels it. */
const version = computed(() => `v${page.props.version}`);
</script>

<template>
    <!--
        `mt-auto` keeps the footer at the bottom of its column instead of
        trailing the content, so every layout must render it as the last child
        of a full-height flex column.
    -->
    <footer class="mt-auto px-6 py-4 text-center text-xs text-muted-foreground">
        <!-- Plain text on purpose: the version is information, not a link. -->
        <p>{{ version }}</p>
        <p v-if="branding.footerText">{{ branding.footerText }}</p>
    </footer>
</template>
