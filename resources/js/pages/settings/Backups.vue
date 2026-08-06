<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, Play, RotateCcw, Trash2, Upload } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { formatBrowserDateTime } from '@/lib/dateTime';
import { index as settingsIndex } from '@/routes/settings';
import {
    destroy,
    download,
    index,
    restore,
    store,
    upload,
} from '@/routes/settings/backups';

type BackupArchive = {
    filename: string;
    disk: string;
    size_in_bytes: number;
    created_at: string;
};

type BackupRun = {
    id: string;
    type: 'backup' | 'restore';
    status: 'pending' | 'running' | 'completed' | 'failed';
    filename: string | null;
    size_in_bytes: number | null;
    error_code: string | null;
    started_by: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string | null;
};

const props = defineProps<{
    dateFormat: string;
    archives: BackupArchive[];
    runs: BackupRun[];
    activeRun: BackupRun | null;
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'setting.layout.title', href: settingsIndex() },
        { key: 'backup.title', href: index() },
    ]),
});

/**
 * How often the page asks for fresh state while a run is in flight.
 *
 * A backup takes minutes, so a tight interval buys nothing but requests.
 */
const POLL_INTERVAL_MS = 5000;

const page = usePage();
const { t } = useTranslations();

/*
 * The pending target and the dialog's open state are deliberately two refs.
 *
 * `AlertDialogAction` forwards `@click` as a fallthrough attribute, and Vue runs
 * the component's own handler before the inherited one - so Reka closes the
 * dialog first. If closing also cleared the target, the confirm handler would
 * run against null and silently send nothing. Keeping the target across the
 * close makes the order irrelevant.
 */
const deletingArchive = ref<BackupArchive | null>(null);
const isDeleteDialogOpen = ref(false);

/* Same two-ref split, for the same reason. */
const restoringArchive = ref<BackupArchive | null>(null);
const isRestoreDialogOpen = ref(false);

const isUploadDialogOpen = ref(false);
const uploadForm = useForm({
    archive: null as File | null,
});

/*
 * Bumped to remount the file input. Resetting the form clears the File it holds
 * but not the element's own value, and an element that still holds the same file
 * fires no `change` when the operator picks that file again - so after a
 * rejected upload the fix looks broken until they choose a different file.
 */
const uploadInputKey = ref(0);

const resetUpload = () => {
    uploadForm.reset();
    uploadForm.clearErrors();
    uploadInputKey.value += 1;
};

const requestDelete = (archive: BackupArchive) => {
    deletingArchive.value = archive;
    isDeleteDialogOpen.value = true;
};

const requestRestore = (archive: BackupArchive) => {
    restoringArchive.value = archive;
    isRestoreDialogOpen.value = true;
};

const isRunning = computed(() => props.activeRun !== null);

let pollTimer: ReturnType<typeof setInterval> | null = null;

const stopPolling = () => {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const startPolling = () => {
    if (pollTimer !== null) {
        return;
    }

    pollTimer = setInterval(() => {
        /*
         * Only the props that can change while a run is in flight. A full visit
         * would rebuild the page and fight with anything the user is doing.
         */
        router.reload({ only: ['activeRun', 'runs', 'archives'] });
    }, POLL_INTERVAL_MS);
};

/*
 * Polling exists only for the duration of a run. An idle page must issue no
 * requests at all - an interval left running on an admin page is a leak nobody
 * notices until it shows up in the logs.
 */
watch(
    isRunning,
    (running) => {
        if (running) {
            startPolling();

            return;
        }

        stopPolling();
    },
    { immediate: true },
);

onBeforeUnmount(stopPolling);

const formatDateTime = (value: string | null) =>
    formatBrowserDateTime(value, props.dateFormat, page.props.locale);

const formatSize = (bytes: number | null) => {
    if (bytes === null) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    const exponent = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length,
    );
    const value = bytes / 1024 ** exponent;
    const formatted = value >= 10 ? value.toFixed(0) : value.toFixed(1);

    return `${formatted.replace(/\.0$/u, '')} ${units[exponent - 1]}`;
};

const runBackup = () => {
    router.post(store().url, {}, { preserveScroll: true });
};

const confirmDelete = () => {
    const archive = deletingArchive.value;

    if (archive === null) {
        return;
    }

    router.delete(destroy(archive.filename).url, { preserveScroll: true });
    isDeleteDialogOpen.value = false;
    deletingArchive.value = null;
};

const confirmRestore = () => {
    const archive = restoringArchive.value;

    if (archive === null) {
        return;
    }

    router.post(restore(archive.filename).url, {}, { preserveScroll: true });
    isRestoreDialogOpen.value = false;
    restoringArchive.value = null;
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        uploadForm.archive = target.files[0];
    }
};

const submitUpload = () => {
    if (!uploadForm.archive) {
        return;
    }

    uploadForm.post(upload().url, {
        preserveScroll: true,
        onSuccess: () => {
            isUploadDialogOpen.value = false;
            resetUpload();
        },
    });
};

const closeUpload = () => {
    isUploadDialogOpen.value = false;
    resetUpload();
};
</script>

<template>
    <div class="contents">
        <Head :title="t('backup.title')" />
        <PageWrapper
            :title="t('backup.title')"
            :description="t('backup.description')"
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        :disabled="isRunning"
                        data-test="upload-backup"
                        @click="isUploadDialogOpen = true"
                    >
                        <Upload data-icon="inline-start" />{{
                            t('backup.action.upload')
                        }}
                    </Button>
                    <Button
                        :disabled="isRunning"
                        data-test="run-backup"
                        @click="runBackup"
                    >
                        <Play data-icon="inline-start" />{{
                            isRunning
                                ? t('backup.action.running')
                                : t('backup.action.run')
                        }}
                    </Button>
                </div>
            </template>

            <div class="flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('backup.archive.title') }}</CardTitle>
                        <CardDescription>{{
                            t('backup.archive.description')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{
                                        t('backup.archive.filename')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.archive.created_at')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.archive.size')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.archive.disk')
                                    }}</TableHead>
                                    <TableHead class="text-right" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="archive in archives"
                                    :key="`${archive.disk}:${archive.filename}`"
                                    data-test="archive-row"
                                >
                                    <TableCell class="font-medium">{{
                                        archive.filename
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        formatDateTime(archive.created_at)
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        formatSize(archive.size_in_bytes)
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        archive.disk
                                    }}</TableCell>
                                    <TableCell>
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                :disabled="isRunning"
                                                :aria-label="
                                                    t('backup.action.restore')
                                                "
                                                data-test="restore-archive"
                                                @click="requestRestore(archive)"
                                                ><RotateCcw
                                            /></Button>
                                            <Button
                                                as-child
                                                size="icon"
                                                variant="ghost"
                                                :aria-label="
                                                    t('backup.action.download')
                                                "
                                            >
                                                <a
                                                    :href="
                                                        download(
                                                            archive.filename,
                                                        ).url
                                                    "
                                                    data-test="download-archive"
                                                    ><Download
                                                /></a>
                                            </Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                :aria-label="
                                                    t('backup.action.delete')
                                                "
                                                data-test="delete-archive"
                                                @click="requestDelete(archive)"
                                                ><Trash2
                                            /></Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableEmpty
                                    v-if="archives.length === 0"
                                    :colspan="5"
                                    >{{ t('backup.archive.empty') }}</TableEmpty
                                >
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('backup.run.title') }}</CardTitle>
                        <CardDescription>{{
                            t('backup.run.description')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{
                                        t('backup.run.type')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.run.status')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.run.started_by')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.run.started_at')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('backup.run.completed_at')
                                    }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="run in runs"
                                    :key="run.id"
                                    data-test="run-row"
                                >
                                    <TableCell data-test="run-type">{{
                                        t(`backup.type.${run.type}`)
                                    }}</TableCell>
                                    <TableCell>
                                        <div class="flex flex-col gap-1">
                                            <Badge
                                                :variant="
                                                    run.status === 'failed'
                                                        ? 'destructive'
                                                        : run.status ===
                                                            'completed'
                                                          ? 'default'
                                                          : 'secondary'
                                                "
                                                >{{
                                                    t(
                                                        `backup.status.${run.status}`,
                                                    )
                                                }}</Badge
                                            >
                                            <span
                                                v-if="run.error_code"
                                                class="text-xs text-destructive"
                                                data-test="run-error"
                                                >{{
                                                    t(
                                                        `backup.error.${run.error_code}`,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        run.started_by ??
                                        t('backup.run.scheduled')
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        formatDateTime(run.started_at)
                                    }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{
                                        formatDateTime(run.completed_at)
                                    }}</TableCell>
                                </TableRow>
                                <TableEmpty
                                    v-if="runs.length === 0"
                                    :colspan="5"
                                    >{{ t('backup.run.empty') }}</TableEmpty
                                >
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </PageWrapper>

        <AlertDialog
            :open="isDeleteDialogOpen"
            @update:open="
                (value: boolean) => {
                    isDeleteDialogOpen = value;
                }
            "
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{
                        t('backup.confirm.delete.title')
                    }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        t('backup.confirm.delete.description', {
                            filename: deletingArchive?.filename ?? '',
                        })
                    }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{{
                        t('backup.confirm.delete.cancel')
                    }}</AlertDialogCancel>
                    <AlertDialogAction
                        data-test="confirm-delete-archive"
                        @click="confirmDelete"
                        >{{
                            t('backup.confirm.delete.confirm')
                        }}</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog
            :open="isRestoreDialogOpen"
            @update:open="
                (value: boolean) => {
                    isRestoreDialogOpen = value;
                }
            "
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{
                        t('backup.confirm.restore.title')
                    }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        t('backup.confirm.restore.description', {
                            filename: restoringArchive?.filename ?? '',
                        })
                    }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{{
                        t('backup.confirm.restore.cancel')
                    }}</AlertDialogCancel>
                    <AlertDialogAction
                        data-test="confirm-restore-archive"
                        @click="confirmRestore"
                        >{{
                            t('backup.confirm.restore.confirm')
                        }}</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Dialog
            :open="isUploadDialogOpen"
            @update:open="
                (value: boolean) => {
                    isUploadDialogOpen = value;

                    if (!value) {
                        resetUpload();
                    }
                }
            "
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        t('backup.confirm.upload.title')
                    }}</DialogTitle>
                    <DialogDescription>{{
                        t('backup.confirm.upload.description')
                    }}</DialogDescription>
                </DialogHeader>
                <div class="grid w-full items-center gap-4 py-4">
                    <Input
                        :key="uploadInputKey"
                        type="file"
                        accept=".zip,application/zip"
                        :aria-label="t('backup.confirm.upload.select')"
                        data-test="upload-archive-input"
                        @change="handleFileSelect"
                    />
                    <span
                        v-if="uploadForm.errors.archive"
                        class="text-sm text-destructive"
                        >{{ uploadForm.errors.archive }}</span
                    >
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        data-test="cancel-upload-archive"
                        @click="closeUpload"
                    >
                        {{ t('backup.confirm.upload.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="uploadForm.processing || !uploadForm.archive"
                        data-test="confirm-upload-archive"
                        @click="submitUpload"
                    >
                        {{ t('backup.confirm.upload.confirm') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
