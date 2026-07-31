import type { JSONContent } from '@tiptap/core';

export type TiptapDocument = JSONContent & { type: 'doc' };

export type DocumentationSummary = {
    id: string;
    title: string;
    slug: string;
    status: 'draft' | 'published';
    position: number;
    category?: DocumentationCategory;
};

export type DocumentationCategory = {
    id: string;
    name: string;
    position: number;
    documentations_count?: number;
    documentations?: DocumentationSummary[];
};

export type DocumentationDetail = DocumentationSummary & {
    documentation_category_id: string;
    content: TiptapDocument;
    published_at: string | null;
    category: DocumentationCategory;
};
