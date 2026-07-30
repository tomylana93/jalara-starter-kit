import { describe, expect, it } from 'vitest';
import { DATE_TIME_FALLBACK, formatBrowserDateTime } from './dateTime';

/*
 * 22:30 UTC on the 30th is already 05:30 on the 31st in the configured test
 * timezone (Asia/Jakarta, UTC+7), so every assertion below would fail if the
 * formatter silently used UTC.
 */
const lateEveningUtc = '2026-07-30T22:30:00.000000Z';

describe('formatBrowserDateTime', () => {
    it('renders the instant in the browser timezone, not UTC', () => {
        expect(formatBrowserDateTime(lateEveningUtc, 'Y-m-d')).toBe(
            '2026-07-31 05:30',
        );
        expect(lateEveningUtc).toContain('2026-07-30');
    });

    it('maps every configured date format preset', () => {
        expect(formatBrowserDateTime(lateEveningUtc, 'Y-m-d')).toBe(
            '2026-07-31 05:30',
        );
        expect(formatBrowserDateTime(lateEveningUtc, 'd/m/Y')).toBe(
            '31/07/2026 05:30',
        );
        expect(formatBrowserDateTime(lateEveningUtc, 'm/d/Y')).toBe(
            '07/31/2026 05:30',
        );
        expect(formatBrowserDateTime(lateEveningUtc, 'd M Y')).toBe(
            '31 Jul 2026 05:30',
        );
    });

    it('localizes the month name of the textual preset', () => {
        expect(
            formatBrowserDateTime('2026-08-15T03:00:00.000000Z', 'd M Y'),
        ).toBe('15 Aug 2026 10:00');
        expect(
            formatBrowserDateTime('2026-08-15T03:00:00.000000Z', 'd M Y', 'id'),
        ).toBe('15 Agu 2026 10:00');
    });

    it('keeps a 24 hour clock past noon', () => {
        expect(
            formatBrowserDateTime('2026-07-30T10:05:00.000000Z', 'Y-m-d'),
        ).toBe('2026-07-30 17:05');
    });

    it('falls back to the ISO ordering for an unknown preset', () => {
        expect(formatBrowserDateTime(lateEveningUtc, 'r-a-n-d-o-m')).toBe(
            '2026-07-31 05:30',
        );
    });

    it('returns a safe fallback for a missing or unparseable instant', () => {
        expect(formatBrowserDateTime(null, 'Y-m-d')).toBe(DATE_TIME_FALLBACK);
        expect(formatBrowserDateTime(undefined, 'Y-m-d')).toBe(
            DATE_TIME_FALLBACK,
        );
        expect(formatBrowserDateTime('', 'Y-m-d')).toBe(DATE_TIME_FALLBACK);
        expect(formatBrowserDateTime('not-a-date', 'Y-m-d')).toBe(
            DATE_TIME_FALLBACK,
        );
    });
});
