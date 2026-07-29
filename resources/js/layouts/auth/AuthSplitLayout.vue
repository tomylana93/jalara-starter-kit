<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import BrandIdentity from '@/components/BrandIdentity.vue';
import { useBranding } from '@/composables/useBranding';
import { home } from '@/routes';

const { branding } = useBranding();

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
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <!--
                The image is decorative; the solid colour stays as the base so
                the panel is never empty while it loads.
            -->
            <div class="absolute inset-0 bg-zinc-900" />
            <img
                :src="backgroundUrl"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 size-full object-cover"
            />
            <!-- Keeps the heading legible whatever the image contains. -->
            <div class="absolute inset-0 bg-zinc-900/60" />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <BrandIdentity class="[&_span]:text-white" />
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
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
