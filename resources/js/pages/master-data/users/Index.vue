<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileSpreadsheet, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { DataTable } from '@/components/data-table';
import type {
    TableFilterConfig,
    TableFilterOption,
    TablePayload,
    TableQuery,
} from '@/components/data-table';
import PageWrapper from '@/components/PageWrapper.vue';
import { buttonVariants } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as masterDataIndex } from '@/routes/master-data';
import { create, exportMethod, index } from '@/routes/master-data/users';
import { createUserColumns } from './columns';
import type { UserRow } from './columns';

const props = defineProps<{
    users: TablePayload<UserRow>;
    filterOptions: Record<string, TableFilterOption[]>;
    canCreate: boolean;
    dateFormat: string;
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'master_data.layout.title', href: masterDataIndex() },
        { key: 'master_data.user.title', href: index() },
    ]),
});

const { t } = useTranslations();
const page = usePage();

const columns = computed(() =>
    createUserColumns(t, props.dateFormat, page.props.locale),
);

/*
 * The table is domain free, so the page names the filters and labels them; the
 * keys are exactly what the server validates.
 */
const filters = computed<TableFilterConfig[]>(() =>
    (['status', 'role'] as const).map((key) => ({
        key,
        label: t(`master_data.user.filter.${key}`),
        options: props.filterOptions[key] ?? [],
    })),
);

const selectedUserIds = ref<string[]>([]);

/*
 * A plain browser download rather than an Inertia visit: the response is a
 * file, and the selection order is what the spreadsheet rows follow.
 */
const exportUrl = computed(() =>
    exportMethod.url({ query: { ids: selectedUserIds.value } }),
);

/**
 * Ask the server for the next slice of rows.
 *
 * The table is fully server driven, so every state change becomes a partial
 * reload that also parks the query in the URL.
 */
const applyQuery = (query: TableQuery): void => {
    router.get(
        index.url({
            query: {
                ...query.filters,
                search: query.search,
                sort: query.sort,
                direction: query.direction,
                page: query.page,
                perPage: query.perPage,
            },
        }),
        {},
        {
            only: ['users'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};
</script>

<template>
    <div class="contents">
        <Head :title="t('master_data.user.title')" />

        <PageWrapper
            :title="t('master_data.user.title')"
            :description="t('master_data.user.description')"
        >
            <template v-if="props.canCreate" #actions>
                <Link
                    :href="create()"
                    :class="buttonVariants()"
                    data-test="create-user-button"
                >
                    <Plus class="size-4" />
                    {{ t('master_data.user.button.create') }}
                </Link>
            </template>

            <DataTable
                :columns="columns"
                :payload="props.users"
                :filters="filters"
                :get-row-id="(user) => user.id"
                :search-placeholder="t('master_data.user.placeholder.search')"
                :empty-title="t('master_data.user.empty.title')"
                :empty-description="t('master_data.user.empty.description')"
                @query-change="applyQuery"
                @selection-change="selectedUserIds = $event"
            >
                <template #actions>
                    <a
                        v-if="selectedUserIds.length > 0"
                        :href="exportUrl"
                        :class="buttonVariants({ variant: 'outline' })"
                        data-test="export-users-button"
                    >
                        <FileSpreadsheet class="size-4" />
                        {{ t('master_data.user.button.export') }}
                    </a>
                </template>
            </DataTable>
        </PageWrapper>
    </div>
</template>
