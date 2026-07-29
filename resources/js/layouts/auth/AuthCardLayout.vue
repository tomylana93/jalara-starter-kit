<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
import BrandIdentity from '@/components/BrandIdentity.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-muted">
        <!-- The card stays centred in the space the footer leaves behind. -->
        <div
            class="flex flex-1 flex-col items-center justify-center gap-6 p-6 md:p-10"
        >
            <div class="flex w-full max-w-md flex-col gap-6">
                <Link
                    :href="home()"
                    class="flex items-center gap-2 self-center font-medium"
                >
                    <BrandIdentity hide-name />
                </Link>

                <div class="flex flex-col gap-6">
                    <Card class="rounded-xl">
                        <CardHeader class="px-10 pt-8 pb-0 text-center">
                            <CardTitle class="text-xl">{{ title }}</CardTitle>
                            <CardDescription>
                                {{ description }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="px-10 py-8">
                            <slot />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
        <AppFooter />
    </div>
</template>
