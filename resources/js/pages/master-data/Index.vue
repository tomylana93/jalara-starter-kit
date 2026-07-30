<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Users } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import PageWrapper from '@/components/PageWrapper.vue';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/master-data';
import { index as usersIndex } from '@/routes/master-data/users';
import type { RouteDefinition } from '@/wayfinder';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

type MasterDataCard = {
    key: string;
    title: string;
    description: string;
    href: RouteDefinition<'get'>;
    icon: LucideIcon;
};

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'master_data.layout.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

const { t } = useTranslations();

const masterDataCards = computed<MasterDataCard[]>(() => [
    {
        key: 'users',
        title: t('master_data.user.title'),
        description: t('master_data.user.description'),
        href: usersIndex(),
        icon: Users,
    },
]);
</script>

<template>
    <div class="contents">
        <Head :title="t('master_data.layout.title')" />

        <PageWrapper
            :title="t('master_data.layout.title')"
            :description="t('master_data.layout.description')"
            content-class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="card in masterDataCards"
                :key="card.key"
                :href="card.href"
                :data-test="`master-data-card-${card.key}`"
                class="group rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                <Card
                    class="h-full gap-4 transition-colors group-hover:bg-accent/50"
                >
                    <CardHeader class="gap-4">
                        <div class="flex items-center justify-between gap-4">
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-muted text-muted-foreground transition-colors group-hover:text-foreground"
                            >
                                <component :is="card.icon" class="size-5" />
                            </div>
                            <ArrowRight
                                class="size-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-foreground"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <CardTitle>{{ card.title }}</CardTitle>
                            <CardDescription>
                                {{ card.description }}
                            </CardDescription>
                        </div>
                    </CardHeader>
                </Card>
            </Link>
        </PageWrapper>
    </div>
</template>
