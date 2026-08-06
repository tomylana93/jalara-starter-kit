<script setup lang="ts">
import type { Component } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppFooter from '@/components/AppFooter.vue';
import BrandIdentity from '@/components/BrandIdentity.vue';

/*
 * The shell for pages that report a state rather than carry an application
 * screen: maintenance and errors. It deliberately does not follow the auth
 * layout presets - those are a branding choice for the sign-in experience,
 * while this screen must read the same whichever preset is stored.
 *
 * The icon arrives as a prop rather than a slot because Inertia renders a page
 * into the layout's default slot; a named slot declared in a page template
 * never reaches the layout.
 */
defineProps<{
    title?: string;
    description?: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <div class="absolute top-4 right-4 z-50 sm:top-6 sm:right-6">
            <AppearanceToggle />
        </div>

        <!-- The content stays centred in the space the footer leaves behind. -->
        <div
            class="flex flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10"
        >
            <div class="flex w-full max-w-md flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <BrandIdentity hide-name class="mb-1" />

                    <div
                        v-if="icon"
                        class="flex size-12 items-center justify-center rounded-full bg-muted"
                        data-test="status-icon"
                    >
                        <component
                            :is="icon"
                            class="size-6 text-muted-foreground"
                        />
                    </div>

                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>

                <slot />
            </div>
        </div>

        <AppFooter />
    </div>
</template>
