import type { RichTextDocument } from '@/types/editor';

export type DocumentationStatus = 'draft' | 'published';

/**
 * A category reduced to what a select option or a byline needs.
 */
export type DocumentationCategoryOption = {
    id: string;
    name: string;
};

/**
 * A category in the management sidebar, with the size of its contents.
 */
export type DocumentationManagementCategory = DocumentationCategoryOption & {
    position: number;
    documentations_count: number;
};

/**
 * One row of the paginated management table.
 */
export type DocumentationManagementRow = {
    id: string;
    title: string;
    slug: string;
    status: DocumentationStatus;
    category: DocumentationCategoryOption;
};

/**
 * The row window the server resolved. The client renders from this rather than
 * from its own optimism about which page it asked for.
 */
export type DocumentationPageMeta = {
    page: number;
    perPage: number;
    perPageOptions: number[];
    total: number;
    lastPage: number;
    from: number | null;
    to: number | null;
};

/**
 * The query the server actually applied. The management list exposes no search,
 * sort, or page size control, so this is reported but never negotiated.
 */
export type DocumentationTableState = {
    search: string | null;
    sort: string;
    direction: string;
    perPage: number;
    filters: Record<string, string[]>;
};

export type DocumentationTablePayload = {
    data: DocumentationManagementRow[];
    meta: DocumentationPageMeta;
    state: DocumentationTableState;
};

/**
 * The document the editor form binds to. A create request receives `null`.
 */
export type DocumentationEditorValue = {
    id: string;
    documentation_category_id: string;
    title: string;
    slug: string;
    status: DocumentationStatus;
    published_at: string | null;
    content: RichTextDocument;
};

/**
 * A document as a link in the reader index and sidebar.
 */
export type DocumentationReaderSummary = {
    id: string;
    title: string;
    slug: string;
};

export type DocumentationReaderCategory = DocumentationCategoryOption & {
    documentations: DocumentationReaderSummary[];
};

export type DocumentationReaderDetail = DocumentationReaderSummary & {
    content: RichTextDocument;
    category: DocumentationCategoryOption;
};
