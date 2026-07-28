<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { branding } = useBranding();
</script>

<template>
    <footer
        v-if="branding.footerText"
        class="px-6 py-4 text-center text-xs text-muted-foreground"
    >
        {{ branding.footerText }}
    </footer>
</template>
