<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Breadcrumb,
    BreadcrumbEllipsis,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

const props = defineProps<Props>();

const { t } = useTranslations();

const firstItem = computed<BreadcrumbItemType | undefined>(
    () => props.breadcrumbs[0],
);

/**
 * The current page, which is only a distinct trail entry once the trail holds
 * more than the root item.
 */
const currentItem = computed<BreadcrumbItemType | undefined>(() =>
    props.breadcrumbs.length > 1
        ? props.breadcrumbs[props.breadcrumbs.length - 1]
        : undefined,
);

/**
 * Levels between the root and the current page. They stay inline on wide
 * viewports and collapse into the ellipsis menu on narrow ones.
 */
const collapsibleItems = computed<BreadcrumbItemType[]>(() =>
    props.breadcrumbs.slice(1, -1),
);
</script>

<template>
    <Breadcrumb v-if="firstItem" class="min-w-0">
        <BreadcrumbList class="flex-nowrap">
            <BreadcrumbItem class="min-w-0">
                <BreadcrumbLink v-if="currentItem" as-child class="truncate">
                    <Link :href="firstItem.href">{{ firstItem.title }}</Link>
                </BreadcrumbLink>
                <BreadcrumbPage v-else class="truncate">
                    {{ firstItem.title }}
                </BreadcrumbPage>
            </BreadcrumbItem>

            <template v-if="currentItem">
                <BreadcrumbSeparator class="shrink-0" />

                <template v-if="collapsibleItems.length > 0">
                    <BreadcrumbItem class="shrink-0 md:hidden">
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                class="flex items-center"
                                :aria-label="t('navigation.breadcrumb.expand')"
                                data-test="breadcrumb-expand"
                            >
                                <BreadcrumbEllipsis class="size-4" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                <DropdownMenuItem
                                    v-for="(item, index) in collapsibleItems"
                                    :key="index"
                                    as-child
                                >
                                    <Link :href="item.href">
                                        {{ item.title }}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator class="shrink-0 md:hidden" />

                    <template
                        v-for="(item, index) in collapsibleItems"
                        :key="index"
                    >
                        <BreadcrumbItem class="hidden min-w-0 md:inline-flex">
                            <BreadcrumbLink as-child class="truncate">
                                <Link :href="item.href">{{ item.title }}</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator class="hidden shrink-0 md:block" />
                    </template>
                </template>

                <BreadcrumbItem class="min-w-0">
                    <BreadcrumbPage class="truncate">
                        {{ currentItem.title }}
                    </BreadcrumbPage>
                </BreadcrumbItem>
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
