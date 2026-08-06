/**
 * Reading the per-row failures an import answers with.
 *
 * The server keys one message per offending cell — `rows.12.email` — because
 * Inertia flattens an error bag to a single message per key, so a shared key
 * would report one problem and silently drop the rest. The number in the key is
 * the spreadsheet's own line, including its header row, so it can be typed
 * straight into a spreadsheet's Go To.
 */

export type ImportRowError = {
    key: string;
    line: number;
    message: string;
};

const ROW_ERROR_PREFIX = 'rows.';

/**
 * The row failures among an import form's errors, ordered by spreadsheet line.
 *
 * Anything else in the bag — the file-level `sheet` message — is left out; it
 * has its own place in the dialog and would read as a row problem here.
 */
export const rowErrorsFrom = (
    errors: Record<string, string | undefined>,
): ImportRowError[] =>
    Object.entries(errors)
        .filter(
            (entry): entry is [string, string] =>
                entry[0].startsWith(ROW_ERROR_PREFIX) &&
                typeof entry[1] === 'string',
        )
        .map(([key, message]) => ({
            key,
            line: Number.parseInt(key.split('.')[1] ?? '', 10),
            message,
        }))
        .sort((first, second) => first.line - second.line);
