<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { fallbackApplicationName } from '@/lib/branding';

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
    /** Hide the application name even in icon mode. */
    hideName?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    iconOnly: false,
    hideName: false,
});

const { branding } = useBranding();
const page = usePage();

/*
 * The visible identity pairs the mark with the application name, never with the
 * company name: the company name is stored for documents such as mail, not for
 * the interface.
 */
const applicationName = computed(
    () =>
        (page.props.name as string | undefined)?.trim() ||
        fallbackApplicationName,
);

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
                :alt="applicationName"
                :class="[
                    'h-8 w-auto object-contain',
                    darkSrc ? 'dark:hidden' : '',
                ]"
            />
            <img
                v-if="darkSrc"
                :src="darkSrc"
                :alt="applicationName"
                class="hidden h-8 w-auto object-contain dark:block"
            />
        </template>

        <template v-else>
            <img
                :src="lightSrc"
                :alt="applicationName"
                :class="[
                    'size-8 shrink-0 rounded-md object-cover',
                    darkSrc ? 'dark:hidden' : '',
                ]"
            />
            <img
                v-if="darkSrc"
                :src="darkSrc"
                :alt="applicationName"
                class="hidden size-8 shrink-0 rounded-md object-cover dark:block"
            />
        </template>

        <span
            v-if="showName"
            class="truncate text-sm leading-tight font-semibold"
        >
            {{ applicationName }}
        </span>
    </div>
</template>
