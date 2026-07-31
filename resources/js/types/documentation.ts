import type { RichTextDocument } from '@/types/editor';

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
    content: RichTextDocument;
    published_at: string | null;
    category: DocumentationCategory;
};
