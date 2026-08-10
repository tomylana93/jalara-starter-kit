import { mergeAttributes, Node } from '@tiptap/core';

/** The only attributes a persisted image node may carry. */
export type DocumentationImageAttributes = {
    src: string;
    alt: string;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        documentationImage: {
            /** Insert an uploaded image at the current selection. */
            setDocumentationImage: (
                attributes: DocumentationImageAttributes,
            ) => ReturnType;
        };
    }
}

/**
 * A block image restricted to images this application published itself.
 *
 * Deliberately narrower than `@tiptap/extension-image`, in two ways that both
 * matter for what a document is allowed to contain:
 *
 * - The attribute set stops at `src` and `alt`. Anything else a pasted or
 *   hand-edited node tried to carry is dropped by the schema before it ever
 *   reaches the server validator.
 * - There is no `parseHTML`. Without one, an `<img>` arriving through the
 *   clipboard produces no node at all, so a remote tracking URL or a `data:`
 *   payload cannot enter a document by paste. The only way to get an image in
 *   is the upload flow, whose result always lives under the managed
 *   documentation prefix that `App\Support\DocumentationContent` enforces.
 */
export const DocumentationImage = Node.create({
    name: 'image',
    group: 'block',
    atom: true,
    draggable: false,

    addAttributes() {
        return {
            src: { default: null },
            alt: { default: null },
        };
    },

    renderHTML({ HTMLAttributes }) {
        return ['img', mergeAttributes(HTMLAttributes)];
    },

    addCommands() {
        return {
            setDocumentationImage:
                (attributes: DocumentationImageAttributes) =>
                ({ commands }) =>
                    commands.insertContent({
                        type: this.name,
                        attrs: attributes,
                    }),
        };
    },
});
