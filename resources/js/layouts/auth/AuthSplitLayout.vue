<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import BrandIdentity from '@/components/BrandIdentity.vue';
import { useBranding } from '@/composables/useBranding';
import { home } from '@/routes';

const { branding } = useBranding();
const page = usePage();

/** The description is optional, so the whole block disappears without one. */
const applicationDescription = computed(
    () => (page.props.description as string | null | undefined)?.trim() || null,
);

/*
 * Kept in the consumer so `branding.authBackgroundUrl` stays nullable and the
 * uploader never mistakes the bundled image for a stored, deletable file.
 */
const backgroundUrl = computed(
    () => branding.value.authBackgroundUrl ?? '/assets/images/auth-bg.jpg',
);

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center bg-background px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-primary p-10 text-primary-foreground lg:flex dark:border-r"
        >
            <!--
                The image is decorative; the solid colour stays as the base so
                the panel is never empty while it loads.
            -->
            <div class="absolute inset-0 bg-primary" />
            <img
                :src="backgroundUrl"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 size-full object-cover"
            />
            <!-- Keeps the heading legible whatever the image contains. -->
            <div class="absolute inset-0 bg-primary/70" />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <BrandIdentity class="[&_span]:text-primary-foreground" />
            </Link>
            <!-- Sits at the foot of the image panel, aligned to its left edge. -->
            <p
                v-if="applicationDescription"
                class="relative z-20 mt-auto max-w-sm text-sm text-primary-foreground/80"
                data-test="auth-split-about"
            >
                {{ applicationDescription }}
            </p>
        </div>
        <!-- The form stays centred in the space the footer leaves behind. -->
        <div class="flex h-full flex-col lg:p-8">
            <div
                class="mx-auto flex w-full flex-1 flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
            <AppFooter />
        </div>
    </div>
</template>
