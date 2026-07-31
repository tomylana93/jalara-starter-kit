<script setup lang="ts">
import {
    Bold,
    Code,
    Heading1,
    Heading2,
    Heading3,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Table2,
    Undo2,
} from '@lucide/vue';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
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
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/composables/useTranslations';
import type { TiptapDocument } from '@/types/documentation';

const props = defineProps<{ modelValue: TiptapDocument }>();
const emit = defineEmits<{ 'update:modelValue': [value: TiptapDocument] }>();
const { t } = useTranslations();
const isLinkDialogOpen = ref(false);
const linkHref = ref('');
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: false }),
        Link.configure({ openOnClick: false }),
        Placeholder.configure({
            placeholder: t('documentation.editor.placeholder'),
        }),
        TableKit.configure({ table: { resizable: true } }),
    ],
    editorProps: {
        attributes: {
            class: 'documentation-content min-h-96 focus:outline-none',
        },
    },
    onUpdate: ({ editor: current }) =>
        emit('update:modelValue', current.getJSON() as TiptapDocument),
});
watch(
    () => props.modelValue,
    (content) => {
        if (
            editor.value &&
            JSON.stringify(editor.value.getJSON()) !== JSON.stringify(content)
        ) {
            editor.value.commands.setContent(content, { emitUpdate: false });
        }
    },
);
function openLinkDialog(): void {
    if (!editor.value) {
        return;
    }

    linkHref.value = String(
        editor.value.getAttributes('link').href ?? 'https://',
    );
    isLinkDialogOpen.value = true;
}
function applyLink(): void {
    if (!editor.value) {
        return;
    }

    const href = linkHref.value.trim();

    if (href === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.value
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href })
            .run();
    }

    isLinkDialogOpen.value = false;
}
const actions = [
    {
        label: t('documentation.editor.action.heading_1'),
        icon: Heading1,
        run: () =>
            editor.value?.chain().focus().toggleHeading({ level: 1 }).run(),
    },
    {
        label: t('documentation.editor.action.heading_2'),
        icon: Heading2,
        run: () =>
            editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
    },
    {
        label: t('documentation.editor.action.heading_3'),
        icon: Heading3,
        run: () =>
            editor.value?.chain().focus().toggleHeading({ level: 3 }).run(),
    },
    {
        label: t('documentation.editor.action.bold'),
        icon: Bold,
        run: () => editor.value?.chain().focus().toggleBold().run(),
    },
    {
        label: t('documentation.editor.action.italic'),
        icon: Italic,
        run: () => editor.value?.chain().focus().toggleItalic().run(),
    },
    {
        label: t('documentation.editor.action.bullet_list'),
        icon: List,
        run: () => editor.value?.chain().focus().toggleBulletList().run(),
    },
    {
        label: t('documentation.editor.action.ordered_list'),
        icon: ListOrdered,
        run: () => editor.value?.chain().focus().toggleOrderedList().run(),
    },
    {
        label: t('documentation.editor.action.quote'),
        icon: Quote,
        run: () => editor.value?.chain().focus().toggleBlockquote().run(),
    },
    {
        label: t('documentation.editor.action.code'),
        icon: Code,
        run: () => editor.value?.chain().focus().toggleCodeBlock().run(),
    },
    {
        label: t('documentation.editor.action.link'),
        icon: LinkIcon,
        run: openLinkDialog,
    },
    {
        label: t('documentation.editor.action.table'),
        icon: Table2,
        run: () =>
            editor.value
                ?.chain()
                .focus()
                .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                .run(),
    },
    {
        label: t('documentation.editor.action.undo'),
        icon: Undo2,
        run: () => editor.value?.chain().focus().undo().run(),
    },
    {
        label: t('documentation.editor.action.redo'),
        icon: Redo2,
        run: () => editor.value?.chain().focus().redo().run(),
    },
];
onBeforeUnmount(() => editor.value?.destroy());
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-background">
        <div class="flex flex-wrap gap-1 border-b bg-muted/30 p-2">
            <TooltipProvider
                v-for="action in actions"
                :key="action.label"
                :delay-duration="0"
            >
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :aria-label="action.label"
                            @click="action.run"
                        >
                            <component :is="action.icon" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{{ action.label }}</TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
        <EditorContent
            v-if="editor"
            :editor="editor"
            class="p-5"
            data-test="documentation-editor"
        />
        <Dialog v-model:open="isLinkDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        t('documentation.editor.link.title')
                    }}</DialogTitle>
                    <DialogDescription>{{
                        t('documentation.editor.link.description')
                    }}</DialogDescription>
                </DialogHeader>
                <form class="flex flex-col gap-2" @submit.prevent="applyLink">
                    <Label class="sr-only" for="documentation-link-href">{{
                        t('documentation.editor.link.label')
                    }}</Label>
                    <Input
                        id="documentation-link-href"
                        v-model="linkHref"
                        :placeholder="
                            t('documentation.editor.link.placeholder')
                        "
                        data-test="documentation-link-input"
                    />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isLinkDialogOpen = false"
                            >{{ t('documentation.editor.link.cancel') }}</Button
                        >
                        <Button
                            type="submit"
                            data-test="documentation-link-submit"
                            >{{ t('documentation.editor.link.submit') }}</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
