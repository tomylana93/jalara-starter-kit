<script setup lang="ts">
import {
    Bold,
    ChevronDown,
    Code,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Table2,
    Undo2,
} from '@lucide/vue';
import { isMacOS } from '@tiptap/core';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table';
import StarterKit from '@tiptap/starter-kit';
import type { Editor } from '@tiptap/vue-3';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import type { Component } from 'vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import {
    ContextMenu,
    ContextMenuCheckboxItem,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuRadioGroup,
    ContextMenuRadioItem,
    ContextMenuSeparator,
    ContextMenuSub,
    ContextMenuSubContent,
    ContextMenuSubTrigger,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldLabel, FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Kbd, KbdGroup } from '@/components/ui/kbd';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/composables/useTranslations';
import type { RichTextDocument } from '@/types/editor';

type ChainedCommands = ReturnType<Editor['chain']>;
type ToolbarControl = {
    value: string;
    label: string;
    icon: Component;
    keys: string[];
    apply: (chain: ChainedCommands) => ChainedCommands;
};

/*
 * Tiptap binds its defaults to `Mod`, which resolves to ⌘ on Apple platforms
 * and Ctrl everywhere else. The hints are expanded through the same check the
 * keymap uses so they can never advertise a key the editor does not listen to.
 */
const modifierKeys: Record<string, string> = isMacOS()
    ? { mod: '⌘', alt: '⌥', shift: '⇧' }
    : { mod: 'Ctrl', alt: 'Alt', shift: 'Shift' };

/** Expands one Tiptap keymap binding into the keys a hint should render. */
function shortcutKeys(binding: string): string[] {
    return binding.split('-').map((key) => modifierKeys[key] ?? key);
}

const props = defineProps<{ modelValue: RichTextDocument }>();
const emit = defineEmits<{ 'update:modelValue': [value: RichTextDocument] }>();
const { t } = useTranslations();
const isLinkDialogOpen = ref(false);
const linkHref = ref('');
/*
 * `useEditor` hands back a plain shallow ref, so Tiptap transactions never
 * invalidate a computed on their own. Every transaction bumps this counter and
 * each toolbar computed reads it, which keeps the rendered state in step with
 * the current selection.
 */
const revision = ref(0);
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: false }),
        Link.configure({ openOnClick: false }),
        Placeholder.configure({
            placeholder: t('editor.placeholder'),
        }),
        TableKit.configure({ table: { resizable: true } }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-content min-h-96 focus:outline-none',
        },
    },
    onUpdate: ({ editor: current }) =>
        emit('update:modelValue', current.getJSON() as RichTextDocument),
    onTransaction: () => {
        revision.value += 1;
    },
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

/**
 * Reads derived state from the live editor while depending on `revision`, so
 * the value is recomputed for every transaction rather than once at setup.
 */
function readEditorState<T>(read: (current: Editor) => T, fallback: T): T {
    void revision.value;

    return editor.value ? read(editor.value) : fallback;
}

/** Reports whether a command would apply without dispatching it. */
function isAvailable(
    apply: (chain: ChainedCommands) => ChainedCommands,
): boolean {
    return readEditorState(
        (current) => apply(current.can().chain()).run(),
        false,
    );
}

function runCommand(apply: (chain: ChainedCommands) => ChainedCommands): void {
    const chain = editor.value?.chain().focus();

    if (chain) {
        apply(chain).run();
    }
}

const headingLevels = [1, 2, 3] as const;
const blockLevels = [
    {
        value: 'paragraph',
        label: t('editor.action.paragraph'),
        keys: shortcutKeys('mod-alt-0'),
        apply: (chain: ChainedCommands) => chain.setParagraph(),
    },
    ...headingLevels.map((level) => ({
        value: `heading_${level}`,
        label: t(`editor.action.heading_${level}`),
        keys: shortcutKeys(`mod-alt-${level}`),
        apply: (chain: ChainedCommands) => chain.setHeading({ level }),
    })),
];
const markControls: ToolbarControl[] = [
    {
        value: 'bold',
        label: t('editor.action.bold'),
        icon: Bold,
        keys: shortcutKeys('mod-B'),
        apply: (chain) => chain.toggleBold(),
    },
    {
        value: 'italic',
        label: t('editor.action.italic'),
        icon: Italic,
        keys: shortcutKeys('mod-I'),
        apply: (chain) => chain.toggleItalic(),
    },
];
const blockControls: ToolbarControl[] = [
    {
        value: 'bulletList',
        label: t('editor.action.bullet_list'),
        icon: List,
        keys: shortcutKeys('mod-shift-8'),
        apply: (chain) => chain.toggleBulletList(),
    },
    {
        value: 'orderedList',
        label: t('editor.action.ordered_list'),
        icon: ListOrdered,
        keys: shortcutKeys('mod-shift-7'),
        apply: (chain) => chain.toggleOrderedList(),
    },
    {
        value: 'blockquote',
        label: t('editor.action.quote'),
        icon: Quote,
        keys: shortcutKeys('mod-shift-B'),
        apply: (chain) => chain.toggleBlockquote(),
    },
    {
        value: 'codeBlock',
        label: t('editor.action.code'),
        icon: Code,
        keys: shortcutKeys('mod-alt-C'),
        apply: (chain) => chain.toggleCodeBlock(),
    },
];
const tableOperations: {
    key: string;
    apply: (chain: ChainedCommands) => ChainedCommands;
}[] = [
    { key: 'add_row_before', apply: (chain) => chain.addRowBefore() },
    { key: 'add_row_after', apply: (chain) => chain.addRowAfter() },
    { key: 'delete_row', apply: (chain) => chain.deleteRow() },
    { key: 'add_column_before', apply: (chain) => chain.addColumnBefore() },
    { key: 'add_column_after', apply: (chain) => chain.addColumnAfter() },
    { key: 'delete_column', apply: (chain) => chain.deleteColumn() },
    { key: 'toggle_header_row', apply: (chain) => chain.toggleHeaderRow() },
    { key: 'delete_table', apply: (chain) => chain.deleteTable() },
];
const insertTable = (chain: ChainedCommands): ChainedCommands =>
    chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true });

const activeBlockLevel = computed(() =>
    readEditorState((current) => {
        const active = headingLevels.find((level) =>
            current.isActive('heading', { level }),
        );

        return active ? `heading_${active}` : 'paragraph';
    }, 'paragraph'),
);
const activeBlockLevelLabel = computed(
    () =>
        blockLevels.find((level) => level.value === activeBlockLevel.value)
            ?.label ?? blockLevels[0].label,
);
const activeMarks = computed(() =>
    readEditorState(
        (current) =>
            markControls
                .filter((control) => current.isActive(control.value))
                .map((control) => control.value),
        [] as string[],
    ),
);
const activeBlocks = computed(() =>
    readEditorState(
        (current) =>
            blockControls
                .filter((control) => current.isActive(control.value))
                .map((control) => control.value),
        [] as string[],
    ),
);
const isInsideTable = computed(() =>
    readEditorState((current) => current.isActive('table'), false),
);
const canInsertTable = computed(() => isAvailable(insertTable));
const canUndo = computed(() => isAvailable((chain) => chain.undo()));
const canRedo = computed(() => isAvailable((chain) => chain.redo()));
const tableOperationAvailability = computed(() =>
    Object.fromEntries(
        tableOperations.map((operation) => [
            operation.key,
            isAvailable(operation.apply),
        ]),
    ),
);
const canSetLink = computed(() =>
    isAvailable((chain) => chain.setLink({ href: 'https://example.com' })),
);
const canBlock = computed(() =>
    blockLevels.some((level) => isAvailable(level.apply)),
);
const canTable = computed(() =>
    isInsideTable.value
        ? Object.values(tableOperationAvailability.value).some(Boolean)
        : canInsertTable.value,
);
const historyControls = computed(() => [
    {
        value: 'undo',
        label: t('editor.action.undo'),
        icon: Undo2,
        keys: shortcutKeys('mod-Z'),
        disabled: !canUndo.value,
        apply: (chain: ChainedCommands) => chain.undo(),
    },
    {
        value: 'redo',
        label: t('editor.action.redo'),
        icon: Redo2,
        keys: shortcutKeys('mod-shift-Z'),
        disabled: !canRedo.value,
        apply: (chain: ChainedCommands) => chain.redo(),
    },
]);

function openLinkDialog(): void {
    if (!editor.value) {
        return;
    }

    linkHref.value = String(
        editor.value.getAttributes('link').href ?? 'https://',
    );
    isLinkDialogOpen.value = true;
}

/**
 * Keeps a keystroke the editor already acted on from reaching application-wide
 * listeners. ProseMirror only calls `preventDefault` on a shortcut its keymap
 * handled, but the event still bubbles to `window`, where the sidebar toggle
 * would collapse the sidebar on the same Ctrl/Cmd+B that applies bold.
 */
function stopHandledShortcut(event: KeyboardEvent): void {
    if (event.defaultPrevented) {
        event.stopPropagation();
    }
}

/**
 * Opens the link dialog once the context menu has finished closing. The menu
 * restores focus to its trigger on close, which would pull focus straight back
 * out of the dialog if both happened in the same tick.
 */
function openLinkDialogFromMenu(): void {
    setTimeout(openLinkDialog);
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

onBeforeUnmount(() => editor.value?.destroy());
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-background">
        <TooltipProvider :delay-duration="0">
            <ScrollArea class="border-b bg-muted/30">
                <div class="flex w-max items-center gap-1 p-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="gap-1"
                                :disabled="!canBlock"
                                :aria-label="t('editor.block.label')"
                                data-test="rich-text-block-trigger"
                                >{{ activeBlockLevelLabel
                                }}<ChevronDown data-icon="inline-end"
                            /></Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start">
                            <DropdownMenuRadioGroup
                                :model-value="activeBlockLevel"
                            >
                                <DropdownMenuRadioItem
                                    v-for="level in blockLevels"
                                    :key="level.value"
                                    :value="level.value"
                                    :disabled="!isAvailable(level.apply)"
                                    :data-test="`rich-text-block-${level.value}`"
                                    @select="runCommand(level.apply)"
                                    >{{ level.label }}</DropdownMenuRadioItem
                                >
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Separator orientation="vertical" class="h-6" />
                    <ToggleGroup
                        type="multiple"
                        size="sm"
                        variant="outline"
                        :model-value="activeMarks"
                        :aria-label="t('editor.group.format')"
                    >
                        <Tooltip
                            v-for="control in markControls"
                            :key="control.value"
                        >
                            <TooltipTrigger as-child>
                                <ToggleGroupItem
                                    :value="control.value"
                                    :disabled="!isAvailable(control.apply)"
                                    :aria-label="control.label"
                                    :data-test="`rich-text-toggle-${control.value}`"
                                    :data-state="
                                        activeMarks.includes(control.value)
                                            ? 'on'
                                            : 'off'
                                    "
                                    @click="runCommand(control.apply)"
                                >
                                    <component :is="control.icon" />
                                </ToggleGroupItem>
                            </TooltipTrigger>
                            <TooltipContent class="flex items-center gap-2">
                                {{ control.label }}
                                <KbdGroup>
                                    <Kbd
                                        v-for="key in control.keys"
                                        :key="key"
                                        >{{ key }}</Kbd
                                    >
                                </KbdGroup>
                            </TooltipContent>
                        </Tooltip>
                    </ToggleGroup>
                    <Separator orientation="vertical" class="h-6" />
                    <ToggleGroup
                        type="multiple"
                        size="sm"
                        variant="outline"
                        :model-value="activeBlocks"
                        :aria-label="t('editor.group.block')"
                    >
                        <Tooltip
                            v-for="control in blockControls"
                            :key="control.value"
                        >
                            <TooltipTrigger as-child>
                                <ToggleGroupItem
                                    :value="control.value"
                                    :disabled="!isAvailable(control.apply)"
                                    :aria-label="control.label"
                                    :data-test="`rich-text-toggle-${control.value}`"
                                    :data-state="
                                        activeBlocks.includes(control.value)
                                            ? 'on'
                                            : 'off'
                                    "
                                    @click="runCommand(control.apply)"
                                >
                                    <component :is="control.icon" />
                                </ToggleGroupItem>
                            </TooltipTrigger>
                            <TooltipContent class="flex items-center gap-2">
                                {{ control.label }}
                                <KbdGroup>
                                    <Kbd
                                        v-for="key in control.keys"
                                        :key="key"
                                        >{{ key }}</Kbd
                                    >
                                </KbdGroup>
                            </TooltipContent>
                        </Tooltip>
                    </ToggleGroup>
                    <Separator orientation="vertical" class="h-6" />
                    <ButtonGroup :aria-label="t('editor.group.insert')">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="!canSetLink"
                                    :aria-label="t('editor.action.link')"
                                    @click="openLinkDialog"
                                >
                                    <LinkIcon />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{{
                                t('editor.action.link')
                            }}</TooltipContent>
                        </Tooltip>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="!canTable"
                                    :aria-label="t('editor.table.label')"
                                    data-test="rich-text-table-trigger"
                                >
                                    <Table2 />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem
                                        v-if="!isInsideTable"
                                        :disabled="!canInsertTable"
                                        data-test="rich-text-table-insert"
                                        @select="runCommand(insertTable)"
                                        >{{
                                            t('editor.table.insert')
                                        }}</DropdownMenuItem
                                    >
                                    <template v-else>
                                        <DropdownMenuItem
                                            v-for="operation in tableOperations"
                                            :key="operation.key"
                                            :disabled="
                                                !tableOperationAvailability[
                                                    operation.key
                                                ]
                                            "
                                            :data-test="`rich-text-table-${operation.key}`"
                                            @select="
                                                runCommand(operation.apply)
                                            "
                                            >{{
                                                t(
                                                    `editor.table.${operation.key}`,
                                                )
                                            }}</DropdownMenuItem
                                        >
                                    </template>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </ButtonGroup>
                    <Separator orientation="vertical" class="h-6" />
                    <ButtonGroup :aria-label="t('editor.group.history')">
                        <Tooltip
                            v-for="control in historyControls"
                            :key="control.value"
                        >
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="control.disabled"
                                    :aria-label="control.label"
                                    @click="runCommand(control.apply)"
                                >
                                    <component :is="control.icon" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent class="flex items-center gap-2">
                                {{ control.label }}
                                <KbdGroup>
                                    <Kbd
                                        v-for="key in control.keys"
                                        :key="key"
                                        >{{ key }}</Kbd
                                    >
                                </KbdGroup>
                            </TooltipContent>
                        </Tooltip>
                    </ButtonGroup>
                </div>
            </ScrollArea>
        </TooltipProvider>
        <ContextMenu>
            <ContextMenuTrigger as-child>
                <div class="p-5" @keydown="stopHandledShortcut">
                    <EditorContent
                        v-if="editor"
                        :editor="editor"
                        data-test="rich-text-editor"
                    />
                </div>
            </ContextMenuTrigger>
            <ContextMenuContent
                class="w-64 *:whitespace-nowrap"
                data-test="rich-text-context-menu"
            >
                <ContextMenuSub>
                    <ContextMenuSubTrigger
                        :disabled="!canBlock"
                        data-test="rich-text-context-block-trigger"
                        >{{ t('editor.block.label') }}</ContextMenuSubTrigger
                    >
                    <ContextMenuSubContent>
                        <ContextMenuRadioGroup :model-value="activeBlockLevel">
                            <ContextMenuRadioItem
                                v-for="level in blockLevels"
                                :key="level.value"
                                :value="level.value"
                                :disabled="!isAvailable(level.apply)"
                                :data-test="`rich-text-context-block-${level.value}`"
                                @select="runCommand(level.apply)"
                            >
                                {{ level.label }}
                                <KbdGroup class="ml-auto shrink-0">
                                    <Kbd v-for="key in level.keys" :key="key">{{
                                        key
                                    }}</Kbd>
                                </KbdGroup>
                            </ContextMenuRadioItem>
                        </ContextMenuRadioGroup>
                    </ContextMenuSubContent>
                </ContextMenuSub>
                <ContextMenuSeparator />
                <ContextMenuCheckboxItem
                    v-for="control in markControls"
                    :key="control.value"
                    :model-value="activeMarks.includes(control.value)"
                    :disabled="!isAvailable(control.apply)"
                    :data-test="`rich-text-context-${control.value}`"
                    @select="runCommand(control.apply)"
                >
                    <component :is="control.icon" />{{ control.label }}
                    <KbdGroup class="ml-auto shrink-0">
                        <Kbd v-for="key in control.keys" :key="key">{{
                            key
                        }}</Kbd>
                    </KbdGroup>
                </ContextMenuCheckboxItem>
                <ContextMenuSeparator />
                <ContextMenuCheckboxItem
                    v-for="control in blockControls"
                    :key="control.value"
                    :model-value="activeBlocks.includes(control.value)"
                    :disabled="!isAvailable(control.apply)"
                    :data-test="`rich-text-context-${control.value}`"
                    @select="runCommand(control.apply)"
                >
                    <component :is="control.icon" />{{ control.label }}
                    <KbdGroup class="ml-auto shrink-0">
                        <Kbd v-for="key in control.keys" :key="key">{{
                            key
                        }}</Kbd>
                    </KbdGroup>
                </ContextMenuCheckboxItem>
                <ContextMenuSeparator />
                <ContextMenuItem
                    :disabled="!canSetLink"
                    data-test="rich-text-context-link"
                    @select="openLinkDialogFromMenu"
                >
                    <LinkIcon />{{ t('editor.action.link') }}
                </ContextMenuItem>
                <ContextMenuSub>
                    <ContextMenuSubTrigger
                        :disabled="!canTable"
                        data-test="rich-text-context-table-trigger"
                        >{{ t('editor.table.label') }}</ContextMenuSubTrigger
                    >
                    <ContextMenuSubContent>
                        <ContextMenuItem
                            v-if="!isInsideTable"
                            :disabled="!canInsertTable"
                            data-test="rich-text-context-table-insert"
                            @select="runCommand(insertTable)"
                            >{{ t('editor.table.insert') }}</ContextMenuItem
                        >
                        <template v-else>
                            <ContextMenuItem
                                v-for="operation in tableOperations"
                                :key="operation.key"
                                :disabled="
                                    !tableOperationAvailability[operation.key]
                                "
                                :data-test="`rich-text-context-table-${operation.key}`"
                                @select="runCommand(operation.apply)"
                                >{{
                                    t(`editor.table.${operation.key}`)
                                }}</ContextMenuItem
                            >
                        </template>
                    </ContextMenuSubContent>
                </ContextMenuSub>
                <ContextMenuSeparator />
                <ContextMenuItem
                    v-for="control in historyControls"
                    :key="control.value"
                    :disabled="control.disabled"
                    :data-test="`rich-text-context-${control.value}`"
                    @select="runCommand(control.apply)"
                >
                    <component :is="control.icon" />{{ control.label }}
                    <KbdGroup class="ml-auto shrink-0">
                        <Kbd v-for="key in control.keys" :key="key">{{
                            key
                        }}</Kbd>
                    </KbdGroup>
                </ContextMenuItem>
            </ContextMenuContent>
        </ContextMenu>
        <Dialog v-model:open="isLinkDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t('editor.link.title') }}</DialogTitle>
                    <DialogDescription>{{
                        t('editor.link.description')
                    }}</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="applyLink">
                    <FieldGroup>
                        <Field>
                            <FieldLabel
                                class="sr-only"
                                for="rich-text-link-href"
                                >{{ t('editor.link.label') }}</FieldLabel
                            >
                            <Input
                                id="rich-text-link-href"
                                v-model="linkHref"
                                :placeholder="t('editor.link.placeholder')"
                                data-test="rich-text-link-input"
                            />
                        </Field>
                    </FieldGroup>
                    <DialogFooter class="mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isLinkDialogOpen = false"
                            >{{ t('editor.link.cancel') }}</Button
                        >
                        <Button
                            type="submit"
                            data-test="rich-text-link-submit"
                            >{{ t('editor.link.submit') }}</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
