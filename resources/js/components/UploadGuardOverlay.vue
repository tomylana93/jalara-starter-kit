<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { useUploadGuard } from '@/composables/useUploadGuard';

const { t } = useTranslations();
const { isUploading, progress } = useUploadGuard();
</script>

<template>
    <!--
        Non-dismissible on purpose: the point is to keep the page still while
        bytes are in flight, so neither Escape nor an outside click closes it.
    -->
    <Dialog :open="isUploading">
        <DialogContent
            class="sm:max-w-sm [&>button]:hidden"
            @escape-key-down.prevent
            @pointer-down-outside.prevent
            @interact-outside.prevent
        >
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Spinner />
                    {{ t('common.upload.guard.title') }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('common.upload.guard.description') }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-1">
                <Progress :model-value="progress" />
                <span class="text-xs text-muted-foreground">
                    {{ progress }}%
                </span>
            </div>
        </DialogContent>
    </Dialog>
</template>
