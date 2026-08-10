<script setup lang="ts">
import { mergeAttributes } from '@tiptap/core';
import Link from '@tiptap/extension-link';
import { TableKit } from '@tiptap/extension-table';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { onBeforeUnmount } from 'vue';
import { DocumentationImage } from '@/extensions/documentationImage';
import type { RichTextDocument } from '@/types/editor';

const props = defineProps<{ content: RichTextDocument }>();
const SafeLink = Link.extend({
    renderHTML({ HTMLAttributes }) {
        const external = /^https?:\/\//i.test(
            String(HTMLAttributes.href ?? ''),
        );

        return [
            'a',
            mergeAttributes(
                this.options.HTMLAttributes,
                HTMLAttributes,
                external
                    ? { target: '_blank', rel: 'noopener noreferrer' }
                    : {},
            ),
            0,
        ];
    },
});
const editor = useEditor({
    content: props.content,
    editable: false,
    extensions: [
        StarterKit.configure({ heading: { levels: [1, 2, 3] }, link: false }),
        SafeLink,
        DocumentationImage,
        TableKit,
    ],
    editorProps: { attributes: { class: 'rich-text-content' } },
});
onBeforeUnmount(() => editor.value?.destroy());
</script>

<template>
    <EditorContent v-if="editor" :editor="editor" />
</template>
