<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import DocumentationRenderer from '@/components/documentation/DocumentationRenderer.vue';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index, show } from '@/routes/documentation';
import type {
    DocumentationCategory,
    DocumentationDetail,
} from '@/types/documentation';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
    documentation: DocumentationDetail;
};

defineProps<{
    documentation: DocumentationDetail;
    categories: DocumentationCategory[];
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'documentation.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
            {
                title: layoutProps.documentation.title,
                href: show(layoutProps.documentation.slug),
            },
        ],
    }),
});

const { t } = useTranslations();
</script>

<template>
    <div class="contents">
        <Head :title="documentation.title" />
        <main
            class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-8 lg:grid-cols-[16rem_minmax(0,1fr)]"
        >
            <aside class="hidden lg:block">
                <ScrollArea class="h-[calc(100vh-8rem)] pr-4">
                    <Link :href="index()" class="text-sm font-medium"
                        >← {{ t('documentation.title') }}</Link
                    >
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="mt-6 flex flex-col gap-2"
                    >
                        <p class="text-sm font-semibold">{{ category.name }}</p>
                        <Link
                            v-for="item in category.documentations"
                            :key="item.id"
                            :href="show(item.slug)"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            {{ item.title }}
                        </Link>
                    </div>
                </ScrollArea>
            </aside>
            <article class="min-w-0">
                <div class="mb-6 flex items-center gap-3 lg:hidden">
                    <Sheet>
                        <SheetTrigger as-child
                            ><Button variant="outline"
                                ><Menu data-icon="inline-start" />{{
                                    t('documentation.reader.list')
                                }}</Button
                            ></SheetTrigger
                        >
                        <SheetContent side="left">
                            <SheetTitle>{{
                                t('documentation.reader.list')
                            }}</SheetTitle>
                            <SheetDescription>{{
                                t('documentation.reader.list_description')
                            }}</SheetDescription>
                            <ScrollArea class="mt-6 h-[calc(100vh-8rem)]">
                                <div
                                    v-for="category in categories"
                                    :key="category.id"
                                    class="mb-6 flex flex-col gap-2"
                                >
                                    <p class="font-semibold">
                                        {{ category.name }}
                                    </p>
                                    <Link
                                        v-for="item in category.documentations"
                                        :key="item.id"
                                        :href="show(item.slug)"
                                        class="text-sm text-muted-foreground"
                                        >{{ item.title }}</Link
                                    >
                                </div>
                            </ScrollArea>
                        </SheetContent>
                    </Sheet>
                </div>
                <p class="mb-2 text-sm text-muted-foreground">
                    {{ documentation.category.name }}
                </p>
                <h1 class="mb-8 text-3xl font-semibold tracking-tight">
                    {{ documentation.title }}
                </h1>
                <DocumentationRenderer :content="documentation.content" />
            </article>
        </main>
    </div>
</template>
