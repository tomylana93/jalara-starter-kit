<script setup lang="ts">
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';

/*
 * Bundled brand files stand in when nothing is stored. They live here in the
 * consumer so `branding.*Url` stays nullable: the uploader must keep treating a
 * missing asset as empty rather than as a stored file it could delete.
 */
const fallbacks = {
    logo: '/assets/images/branding/logo.png',
    logoDark: '/assets/images/branding/logo-dark.png',
    icon: '/assets/images/branding/icon.png',
    iconDark: '/assets/images/branding/icon-dark.png',
} as const;

type Props = {
    /** Compact surfaces show the mark alone, whatever the identity mode is. */
    iconOnly?: boolean;
    /** Hide the company name even in icon mode. */
    hideName?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    iconOnly: false,
    hideName: false,
});

const { branding } = useBranding();

/*
 * Light and dark are chosen by the `.dark` class rather than by reading the
 * appearance in JavaScript: the class already owns the theme, and CSS switching
 * avoids a flash of the wrong asset before hydration.
 *
 * When no dark variant is stored the light one is rendered in both modes, which
 * is the required fallback.
 */
const useLogo = computed(
    () => !props.iconOnly && branding.value.identityMode === 'logo',
);

const showName = computed(() => !props.hideName && !useLogo.value);

const lightSrc = computed(() =>
    useLogo.value
        ? (branding.value.logoUrl ?? fallbacks.logo)
        : (branding.value.iconUrl ?? fallbacks.icon),
);

/*
 * A stored light asset covers both modes on its own, so the bundled dark file
 * only applies while nothing at all is stored.
 */
const darkSrc = computed(() => {
    if (useLogo.value) {
        return (
            branding.value.logoDarkUrl ??
            (branding.value.logoUrl === null ? fallbacks.logoDark : null)
        );
    }

    return (
        branding.value.iconDarkUrl ??
        (branding.value.iconUrl === null ? fallbacks.iconDark : null)
    );
});
</script>

<template>
    <div class="flex items-center gap-2">
        <template v-if="useLogo">
            <img
                :src="lightSrc"
                :alt="branding.companyName"
                :class="[
                    'h-8 w-auto object-contain',
                    darkSrc ? 'dark:hidden' : '',
                ]"
            />
            <img
                v-if="darkSrc"
                :src="darkSrc"
                :alt="branding.companyName"
                class="hidden h-8 w-auto object-contain dark:block"
            />
        </template>

        <template v-else>
            <img
                :src="lightSrc"
                :alt="branding.companyName"
                :class="[
                    'size-8 shrink-0 rounded-md object-cover',
                    darkSrc ? 'dark:hidden' : '',
                ]"
            />
            <img
                v-if="darkSrc"
                :src="darkSrc"
                :alt="branding.companyName"
                class="hidden size-8 shrink-0 rounded-md object-cover dark:block"
            />
        </template>

        <span
            v-if="showName"
            class="truncate text-sm leading-tight font-semibold"
        >
            {{ branding.companyName }}
        </span>
    </div>
</template>
