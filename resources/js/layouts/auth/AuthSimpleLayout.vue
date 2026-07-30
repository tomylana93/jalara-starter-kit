<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
import BrandIdentity from '@/components/BrandIdentity.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <!-- The form stays centred in the space the footer leaves behind. -->
        <div
            class="flex flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10"
        >
            <div class="flex w-full max-w-sm flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <BrandIdentity hide-name class="mb-1" />
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
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
