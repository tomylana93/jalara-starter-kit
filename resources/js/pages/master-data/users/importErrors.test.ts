import { describe, expect, it } from 'vitest';
import { rowErrorsFrom } from './importErrors';

describe('import row errors', () => {
    it('orders the failures by spreadsheet line rather than by key', () => {
        const errors = {
            'rows.31.role': 'Row 31: the role is invalid.',
            'rows.2.email': 'Row 2: the email has already been taken.',
            'rows.12.name': 'Row 12: the name is required.',
        };

        expect(rowErrorsFrom(errors).map((error) => error.line)).toEqual([
            2, 12, 31,
        ]);
    });

    it('carries the message and a stable key for each failure', () => {
        const errors = { 'rows.2.email': 'Row 2: the email is invalid.' };

        expect(rowErrorsFrom(errors)).toEqual([
            {
                key: 'rows.2.email',
                line: 2,
                message: 'Row 2: the email is invalid.',
            },
        ]);
    });

    it('leaves the file level failure out of the row list', () => {
        const errors = {
            sheet: 'The file could not be read as an XLSX spreadsheet.',
            'rows.2.email': 'Row 2: the email is invalid.',
        };

        const rowErrors = rowErrorsFrom(errors);

        expect(rowErrors).toHaveLength(1);
        expect(rowErrors[0]?.key).toBe('rows.2.email');
    });

    it('reports nothing when the bag holds no row failures', () => {
        expect(rowErrorsFrom({})).toEqual([]);
        expect(rowErrorsFrom({ sheet: 'Unreadable.' })).toEqual([]);
    });
});
