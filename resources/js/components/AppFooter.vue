<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { branding } = useBranding();
</script>

<template>
    <!--
        `mt-auto` keeps the footer at the bottom of its column instead of
        trailing the content, so every layout must render it as the last child
        of a full-height flex column.
    -->
    <footer
        v-if="branding.footerText"
        class="mt-auto px-6 py-4 text-center text-xs text-muted-foreground"
    >
        {{ branding.footerText }}
    </footer>
</template>
