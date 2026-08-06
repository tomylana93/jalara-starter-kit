import { Ban, FileQuestion, ServerCrash } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

/*
 * These live outside the page because `defineOptions()` is hoisted out of
 * `setup()` and may not reference anything declared in `<script setup>`; an
 * imported binding is the only way its layout callback can reach them.
 *
 * A status is mapped to a translation key rather than to copy, so a status the
 * exception handler forwards without an entry here still renders the generic
 * failure screen instead of an empty one.
 */
const translationKeys: Record<number, string> = {
    403: 'forbidden',
    404: 'not_found',
    500: 'server_error',
};

const icons: Record<number, LucideIcon> = {
    403: Ban,
    404: FileQuestion,
    500: ServerCrash,
};

export const errorTranslationKey = (status: number): string =>
    translationKeys[status] ?? 'server_error';

export const errorIcon = (status: number): LucideIcon =>
    icons[status] ?? ServerCrash;
