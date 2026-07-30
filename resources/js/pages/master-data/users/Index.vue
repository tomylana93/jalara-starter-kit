<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { DataTable } from '@/components/data-table';
import type { TablePayload, TableQuery } from '@/components/data-table';
import PageWrapper from '@/components/PageWrapper.vue';
import { buttonVariants } from '@/components/ui/button';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as masterDataIndex } from '@/routes/master-data';
import { create, index } from '@/routes/master-data/users';
import { createUserColumns } from './columns';
import type { UserRow } from './columns';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    users: TablePayload<UserRow>;
    canCreate: boolean;
    dateFormat: string;
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'master_data.layout.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: masterDataIndex(),
            },
            {
                title: translate(
                    'master_data.user.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

const { t } = useTranslations();
const page = usePage();

const columns = computed(() =>
    createUserColumns(t, props.dateFormat, page.props.locale),
);

/* No bulk action consumes this yet; the table just reports what is selected. */
const selectedUserIds = ref<string[]>([]);

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
                :get-row-id="(user) => user.id"
                :search-placeholder="t('master_data.user.placeholder.search')"
                :empty-title="t('master_data.user.empty.title')"
                :empty-description="t('master_data.user.empty.description')"
                @query-change="applyQuery"
                @selection-change="selectedUserIds = $event"
            />
        </PageWrapper>
    </div>
</template>
