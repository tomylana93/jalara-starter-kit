<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/account/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Appearance" />

    <h1 class="sr-only">Appearance</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Appearance"
            description="Update the appearance settings for your account"
        />
        <AppearanceTabs />
    </div>
</template>
