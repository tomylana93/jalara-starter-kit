<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Plus, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { DataTable } from '@/components/data-table';
import type {
    TableFilterConfig,
    TableFilterOption,
    TablePayload,
    TableQuery,
} from '@/components/data-table';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as masterDataIndex } from '@/routes/master-data';
import {
    create,
    exportMethod,
    importMethod,
    index,
} from '@/routes/master-data/users';
import { template } from '@/routes/master-data/users/import';
import { createUserColumns } from './columns';
import type { UserRow } from './columns';
import { rowErrorsFrom } from './importErrors';

const props = defineProps<{
    users: TablePayload<UserRow>;
    filterOptions: Record<string, TableFilterOption[]>;
    canCreate: boolean;
    hasDefaultPassword: boolean;
    dateFormat: string;
}>();

/* A whole rejected sheet can fail on every row; a list that long stops being
   readable long before it stops being complete. */
const MAX_VISIBLE_ERRORS = 20;

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

const isImportDialogOpen = ref(false);
const importForm = useForm({
    sheet: null as File | null,
});

/*
 * Bumped to remount the file input, for the reason `Backups.vue` documents:
 * resetting the form clears the File it holds but not the element's own value,
 * so re-picking the same file after a rejection fires no `change` event and the
 * fix looks broken.
 */
const importInputKey = ref(0);

const resetImport = () => {
    importForm.reset();
    importForm.clearErrors();
    importInputKey.value += 1;
};

const rowErrors = computed(() =>
    rowErrorsFrom(importForm.errors as unknown as Record<string, string>),
);

const visibleRowErrors = computed(() =>
    rowErrors.value.slice(0, MAX_VISIBLE_ERRORS),
);

const hiddenRowErrorCount = computed(() =>
    Math.max(rowErrors.value.length - MAX_VISIBLE_ERRORS, 0),
);

const handleSheetSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        importForm.sheet = target.files[0];
    }
};

const submitImport = () => {
    if (!importForm.sheet) {
        return;
    }

    importForm.post(importMethod().url, {
        preserveScroll: true,
        onSuccess: () => {
            isImportDialogOpen.value = false;
            resetImport();
        },
    });
};

const closeImport = () => {
    isImportDialogOpen.value = false;
    resetImport();
};

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
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        :disabled="!props.hasDefaultPassword"
                        :title="
                            props.hasDefaultPassword
                                ? undefined
                                : t(
                                      'master_data.user.import.message.password_missing',
                                  )
                        "
                        data-test="import-users-button"
                        @click="isImportDialogOpen = true"
                    >
                        <Upload class="size-4" />
                        {{ t('master_data.user.import.button.open') }}
                    </Button>

                    <Link
                        :href="create()"
                        :class="buttonVariants()"
                        data-test="create-user-button"
                    >
                        <Plus class="size-4" />
                        {{ t('master_data.user.button.create') }}
                    </Link>
                </div>
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

        <Dialog
            :open="isImportDialogOpen"
            @update:open="
                (value: boolean) => {
                    isImportDialogOpen = value;

                    if (!value) {
                        resetImport();
                    }
                }
            "
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{
                        t('master_data.user.import.title')
                    }}</DialogTitle>
                    <DialogDescription>{{
                        t('master_data.user.import.description')
                    }}</DialogDescription>
                </DialogHeader>

                <div class="grid w-full items-center gap-4 py-2">
                    <a
                        :href="template.url()"
                        :class="buttonVariants({ variant: 'outline' })"
                        data-test="download-import-template"
                    >
                        <Download class="size-4" />
                        {{ t('master_data.user.import.button.template') }}
                    </a>

                    <p class="text-sm text-muted-foreground">
                        {{ t('master_data.user.import.help') }}
                    </p>

                    <Input
                        :key="importInputKey"
                        type="file"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        :aria-label="t('master_data.user.import.label.file')"
                        data-test="import-users-input"
                        @change="handleSheetSelect"
                    />

                    <span
                        v-if="importForm.errors.sheet"
                        class="text-sm text-destructive"
                        data-test="import-sheet-error"
                        >{{ importForm.errors.sheet }}</span
                    >

                    <ul
                        v-if="visibleRowErrors.length > 0"
                        class="max-h-56 space-y-1 overflow-y-auto text-sm text-destructive"
                        data-test="import-row-errors"
                    >
                        <li v-for="error in visibleRowErrors" :key="error.key">
                            {{ error.message }}
                        </li>
                        <li
                            v-if="hiddenRowErrorCount > 0"
                            class="text-muted-foreground"
                            data-test="import-row-errors-overflow"
                        >
                            {{
                                t('master_data.user.import.error.more', {
                                    count: hiddenRowErrorCount,
                                })
                            }}
                        </li>
                    </ul>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-import-users"
                        @click="closeImport"
                    >
                        {{ t('master_data.user.button.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="importForm.processing || !importForm.sheet"
                        data-test="confirm-import-users"
                        @click="submitImport"
                    >
                        {{ t('master_data.user.import.button.submit') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
