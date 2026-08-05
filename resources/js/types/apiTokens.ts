export type ApiToken = {
    id: string;
    name: string;
    last_used_at: string | null;
    created_at: string | null;
};

/**
 * A token as it exists exactly once: at creation, before only its hash remains.
 */
export type CreatedApiToken = {
    name: string;
    plainText: string;
};
