<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Edit,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as documentationIndex } from '@/routes/documentation';
import { create, index as manageIndex } from '@/routes/documentation/manage';
import {
    destroy as destroyCategory,
    move as moveCategory,
    store as storeCategory,
    update as updateCategory,
} from '@/routes/documentation/manage/categories';
import {
    destroy as destroyDocument,
    edit,
    move as moveDocument,
} from '@/routes/documentation/manage/documents';
import type {
    DocumentationManagementCategory,
    DocumentationManagementRow,
    DocumentationTablePayload,
} from '@/types/documentation';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    categories: DocumentationManagementCategory[];
    documentations: DocumentationTablePayload;
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
                href: documentationIndex(),
            },
            {
                title: translate(
                    'documentation.manage.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: manageIndex(),
            },
        ],
    }),
});

const page = usePage();
const { t } = useTranslations();
const categoryForm = useForm({ name: '' });
const renameForm = useForm({ name: '' });
const renamingCategory = ref<DocumentationManagementCategory | null>(null);
const deletingCategory = ref<DocumentationManagementCategory | null>(null);
const deletingDocument = ref<DocumentationManagementRow | null>(null);

const rows = computed(() => props.documentations.data);
const meta = computed(() => props.documentations.meta);

const summary = computed(() =>
    t('common.table.summary', {
        from: meta.value.from ?? 0,
        to: meta.value.to ?? 0,
        total: meta.value.total,
    }),
);

/*
 * Only the page is negotiable: ordering, page size, and the absence of a search
 * term are fixed by the server, so nothing else has to survive the visit.
 */
function goToPage(page: number): void {
    router.get(
        manageIndex({ query: page === 1 ? {} : { page } }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['documentations'],
        },
    );
}

function addCategory(): void {
    categoryForm.submit(storeCategory(), {
        preserveScroll: true,
        onSuccess: () => categoryForm.reset(),
    });
}

function openRename(category: DocumentationManagementCategory): void {
    renamingCategory.value = category;
    renameForm.clearErrors();
    renameForm.name = category.name;
}

function submitRename(): void {
    if (!renamingCategory.value) {
        return;
    }

    renameForm.submit(updateCategory(renamingCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            renamingCategory.value = null;
        },
    });
}

function confirmCategoryDelete(): void {
    if (!deletingCategory.value) {
        return;
    }

    router.visit(destroyCategory(deletingCategory.value.id), {
        preserveScroll: true,
    });
    deletingCategory.value = null;
}

function confirmDocumentDelete(): void {
    if (!deletingDocument.value) {
        return;
    }

    router.visit(destroyDocument(deletingDocument.value.slug), {
        preserveScroll: true,
    });
    deletingDocument.value = null;
}
</script>

<template>
    <div class="contents">
        <Head :title="t('documentation.manage.title')" />
        <PageWrapper
            :title="t('documentation.manage.title')"
            :description="t('documentation.manage.description')"
        >
            <template #actions
                ><Button as-child
                    ><Link :href="create()" data-test="create-documentation"
                        ><Plus data-icon="inline-start" />{{
                            t('documentation.button.create')
                        }}</Link
                    ></Button
                ></template
            >
            <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <Card>
                    <CardHeader
                        ><CardTitle>{{
                            t('documentation.manage.category.title')
                        }}</CardTitle></CardHeader
                    >
                    <CardContent class="flex flex-col gap-4">
                        <form class="flex gap-2" @submit.prevent="addCategory">
                            <div class="flex flex-1 flex-col gap-1">
                                <Label class="sr-only" for="category-name">{{
                                    t('documentation.manage.category.label')
                                }}</Label
                                ><Input
                                    id="category-name"
                                    v-model="categoryForm.name"
                                    :placeholder="
                                        t(
                                            'documentation.manage.category.placeholder',
                                        )
                                    "
                                    :aria-invalid="
                                        Boolean(categoryForm.errors.name)
                                    "
                                /><InputError
                                    :message="categoryForm.errors.name"
                                />
                            </div>
                            <Button
                                type="submit"
                                size="icon"
                                :disabled="categoryForm.processing"
                                :aria-label="
                                    t('documentation.manage.category.add')
                                "
                                ><Plus
                            /></Button>
                        </form>
                        <InputError :message="page.props.errors.category" />
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="category in categories"
                                :key="category.id"
                                class="flex items-center gap-1 rounded-md border p-2"
                            >
                                <span
                                    class="min-w-0 flex-1 truncate text-sm font-medium"
                                    >{{ category.name }}</span
                                >
                                <Badge variant="secondary">{{
                                    category.documentations_count
                                }}</Badge>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="
                                        t(
                                            'documentation.manage.category.move_up',
                                        )
                                    "
                                    @click="
                                        router.visit(
                                            moveCategory([category.id, 'up']),
                                        )
                                    "
                                    ><ArrowUp
                                /></Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="
                                        t(
                                            'documentation.manage.category.move_down',
                                        )
                                    "
                                    @click="
                                        router.visit(
                                            moveCategory([category.id, 'down']),
                                        )
                                    "
                                    ><ArrowDown
                                /></Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="
                                        t(
                                            'documentation.manage.category.rename',
                                        )
                                    "
                                    @click="openRename(category)"
                                    ><Edit
                                /></Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="
                                        t(
                                            'documentation.manage.category.delete',
                                        )
                                    "
                                    @click="deletingCategory = category"
                                    ><Trash2
                                /></Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader
                        ><CardTitle>{{
                            t('documentation.manage.document.title')
                        }}</CardTitle></CardHeader
                    >
                    <CardContent>
                        <Table data-test="documentation-table">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{
                                        t('documentation.manage.column.title')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t(
                                            'documentation.manage.column.category',
                                        )
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('documentation.manage.column.status')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('documentation.manage.column.actions')
                                    }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="documentation in rows"
                                    :key="documentation.id"
                                    data-test="documentation-row"
                                >
                                    <TableCell class="font-medium">{{
                                        documentation.title
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        documentation.category.name
                                    }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="
                                                documentation.status ===
                                                'published'
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                            >{{
                                                t(
                                                    `documentation.status.${documentation.status}`,
                                                )
                                            }}</Badge
                                        >
                                    </TableCell>
                                    <TableCell>
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                :aria-label="
                                                    t(
                                                        'documentation.manage.document.move_up',
                                                    )
                                                "
                                                @click="
                                                    router.visit(
                                                        moveDocument([
                                                            documentation.slug,
                                                            'up',
                                                        ]),
                                                    )
                                                "
                                                ><ArrowUp
                                            /></Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                :aria-label="
                                                    t(
                                                        'documentation.manage.document.move_down',
                                                    )
                                                "
                                                @click="
                                                    router.visit(
                                                        moveDocument([
                                                            documentation.slug,
                                                            'down',
                                                        ]),
                                                    )
                                                "
                                                ><ArrowDown
                                            /></Button>
                                            <Button
                                                as-child
                                                size="icon"
                                                variant="ghost"
                                                ><Link
                                                    :href="
                                                        edit(documentation.slug)
                                                    "
                                                    :aria-label="
                                                        t(
                                                            'documentation.manage.document.edit',
                                                        )
                                                    "
                                                    ><Edit /></Link
                                            ></Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                :aria-label="
                                                    t(
                                                        'documentation.manage.document.delete',
                                                    )
                                                "
                                                @click="
                                                    deletingDocument =
                                                        documentation
                                                "
                                                ><Trash2
                                            /></Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableEmpty v-if="!rows.length" :colspan="4"
                                    >{{ t('documentation.empty.manage') }}
                                </TableEmpty>
                            </TableBody>
                        </Table>
                        <div
                            v-if="meta.total > 0"
                            class="mt-4 flex flex-col items-center justify-between gap-4 sm:flex-row sm:gap-2"
                        >
                            <p
                                class="text-sm text-muted-foreground"
                                data-test="documentation-summary"
                            >
                                {{ summary }}
                            </p>
                            <Pagination
                                v-if="meta.lastPage > 1"
                                :page="meta.page"
                                :items-per-page="meta.perPage"
                                :total="meta.total"
                                :sibling-count="1"
                                show-edges
                                :aria-label="t('common.table.pagination.label')"
                                class="mx-0 w-auto justify-end"
                                @update:page="goToPage"
                            >
                                <PaginationContent v-slot="{ items: pages }">
                                    <PaginationFirst
                                        :aria-label="
                                            t('common.table.pagination.first')
                                        "
                                        data-test="documentation-first-page"
                                    >
                                        <ChevronsLeft />
                                    </PaginationFirst>
                                    <PaginationPrevious
                                        :aria-label="
                                            t(
                                                'common.table.pagination.previous',
                                            )
                                        "
                                        data-test="documentation-previous-page"
                                    >
                                        <ChevronLeft />
                                    </PaginationPrevious>

                                    <template v-for="(item, at) in pages">
                                        <PaginationItem
                                            v-if="item.type === 'page'"
                                            :key="`page-${item.value}`"
                                            :value="item.value"
                                            :is-active="
                                                item.value === meta.page
                                            "
                                            :data-test="`documentation-page-${item.value}`"
                                        >
                                            {{ item.value }}
                                        </PaginationItem>
                                        <PaginationEllipsis
                                            v-else
                                            :key="`ellipsis-${at}`"
                                            :index="at"
                                        />
                                    </template>

                                    <PaginationNext
                                        :aria-label="
                                            t('common.table.pagination.next')
                                        "
                                        data-test="documentation-next-page"
                                    >
                                        <ChevronRight />
                                    </PaginationNext>
                                    <PaginationLast
                                        :aria-label="
                                            t('common.table.pagination.last')
                                        "
                                        data-test="documentation-last-page"
                                    >
                                        <ChevronsRight />
                                    </PaginationLast>
                                </PaginationContent>
                            </Pagination>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </PageWrapper>
        <Dialog
            :open="Boolean(renamingCategory)"
            @update:open="
                (value) => {
                    if (!value) renamingCategory = null;
                }
            "
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        t('documentation.manage.category.rename')
                    }}</DialogTitle>
                    <DialogDescription>{{
                        t('documentation.manage.category.rename_description')
                    }}</DialogDescription>
                </DialogHeader>
                <form
                    class="flex flex-col gap-2"
                    @submit.prevent="submitRename"
                >
                    <Label class="sr-only" for="rename-category-name">{{
                        t('documentation.manage.category.label')
                    }}</Label>
                    <Input
                        id="rename-category-name"
                        v-model="renameForm.name"
                        :aria-invalid="Boolean(renameForm.errors.name)"
                        data-test="rename-category-input"
                    />
                    <InputError :message="renameForm.errors.name" />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="renamingCategory = null"
                            >{{ t('documentation.button.cancel') }}</Button
                        >
                        <Button
                            type="submit"
                            :disabled="renameForm.processing"
                            data-test="rename-category-submit"
                            >{{ t('documentation.button.save') }}</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        <AlertDialog
            :open="Boolean(deletingCategory)"
            @update:open="
                (value) => {
                    if (!value) deletingCategory = null;
                }
            "
        >
            <AlertDialogContent
                ><AlertDialogHeader
                    ><AlertDialogTitle>{{
                        t('documentation.manage.category.delete_title', {
                            name: deletingCategory?.name ?? '',
                        })
                    }}</AlertDialogTitle
                    ><AlertDialogDescription>{{
                        t('documentation.manage.category.delete_description')
                    }}</AlertDialogDescription></AlertDialogHeader
                ><AlertDialogFooter
                    ><AlertDialogCancel>{{
                        t('documentation.button.cancel')
                    }}</AlertDialogCancel
                    ><AlertDialogAction
                        data-test="confirm-category-delete"
                        @click="confirmCategoryDelete"
                        >{{
                            t('documentation.button.delete')
                        }}</AlertDialogAction
                    ></AlertDialogFooter
                ></AlertDialogContent
            >
        </AlertDialog>
        <AlertDialog
            :open="Boolean(deletingDocument)"
            @update:open="
                (value) => {
                    if (!value) deletingDocument = null;
                }
            "
        >
            <AlertDialogContent
                ><AlertDialogHeader
                    ><AlertDialogTitle>{{
                        t('documentation.manage.document.delete_title')
                    }}</AlertDialogTitle
                    ><AlertDialogDescription>{{
                        t('documentation.manage.document.delete_description')
                    }}</AlertDialogDescription></AlertDialogHeader
                ><AlertDialogFooter
                    ><AlertDialogCancel>{{
                        t('documentation.button.cancel')
                    }}</AlertDialogCancel
                    ><AlertDialogAction
                        data-test="confirm-document-delete"
                        @click="confirmDocumentDelete"
                        >{{
                            t('documentation.button.delete')
                        }}</AlertDialogAction
                    ></AlertDialogFooter
                ></AlertDialogContent
            >
        </AlertDialog>
    </div>
</template>
