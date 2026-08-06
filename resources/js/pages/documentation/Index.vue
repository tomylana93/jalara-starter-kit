<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index, show } from '@/routes/documentation';
import { index as manageIndex } from '@/routes/documentation/manage';
import type { DocumentationReaderCategory } from '@/types/documentation';

defineProps<{ categories: DocumentationReaderCategory[] }>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'documentation.title', href: index() },
    ]),
});

const page = usePage();
const { t } = useTranslations();
</script>

<template>
    <div class="contents">
        <Head :title="t('documentation.title')" />
        <PageWrapper
            :title="t('documentation.title')"
            :description="t('documentation.description')"
        >
            <template v-if="page.props.can.manageDocumentation" #actions>
                <Button as-child variant="outline">
                    <Link :href="manageIndex()"
                        ><Settings2 data-icon="inline-start" />{{
                            t('documentation.button.manage')
                        }}</Link
                    >
                </Button>
            </template>
            <div v-if="categories.length" class="flex flex-col gap-8">
                <section
                    v-for="category in categories"
                    :key="category.id"
                    class="flex flex-col gap-3"
                >
                    <h2 class="text-lg font-semibold">{{ category.name }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="documentation in category.documentations"
                            :key="documentation.id"
                        >
                            <CardHeader
                                ><CardTitle class="text-base">{{
                                    documentation.title
                                }}</CardTitle></CardHeader
                            >
                            <CardContent>
                                <Button
                                    as-child
                                    variant="link"
                                    class="h-auto p-0"
                                >
                                    <Link :href="show(documentation.slug)">{{
                                        t('documentation.button.read')
                                    }}</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
            <div
                v-else
                class="rounded-lg border border-dashed p-10 text-center text-muted-foreground"
            >
                {{ t('documentation.empty.reader') }}
            </div>
        </PageWrapper>
    </div>
</template>
