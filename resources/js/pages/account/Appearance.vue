<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { translate, useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/account/appearance';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineOptions({
    layout: (props: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'account.appearance.title',
                    props.locale,
                    props.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('account.appearance.title')" />

    <h1 class="sr-only">{{ t('account.appearance.title') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('account.appearance.title')"
            :description="t('account.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
