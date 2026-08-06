import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { TEST_TIME_ZONE, withTimeZone } from '@/test/timeZone';
import {
    DATE_TIME_FALLBACK,
    formatBrowserDateTime,
    formatBrowserTime,
    formatRelativeTime,
} from './dateTime';

/*
 * 22:30 UTC on the 30th is already 05:30 on the 31st in the pinned timezone
 * (Asia/Jakarta, UTC+7), so every assertion below would fail if the formatter
 * silently used UTC. The zone is pinned per assertion rather than configured
 * globally, so the suite does not depend on the machine it runs on.
 */
const lateEveningUtc = '2026-07-30T22:30:00.000000Z';

describe('formatBrowserDateTime', () => {
    it('renders the instant in the browser timezone, not UTC', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(formatBrowserDateTime(lateEveningUtc, 'Y-m-d')).toBe(
                '2026-07-31 05:30',
            );
        });

        expect(lateEveningUtc).toContain('2026-07-30');
    });

    it('follows the runtime zone rather than a fixed one', () => {
        withTimeZone('UTC', () => {
            expect(formatBrowserDateTime(lateEveningUtc, 'Y-m-d')).toBe(
                '2026-07-30 22:30',
            );
        });

        withTimeZone('America/New_York', () => {
            expect(formatBrowserDateTime(lateEveningUtc, 'Y-m-d')).toBe(
                '2026-07-30 18:30',
            );
        });
    });

    it('maps every configured date format preset', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
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
    });

    it('localizes the month name of the textual preset', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(
                formatBrowserDateTime('2026-08-15T03:00:00.000000Z', 'd M Y'),
            ).toBe('15 Aug 2026 10:00');
            expect(
                formatBrowserDateTime(
                    '2026-08-15T03:00:00.000000Z',
                    'd M Y',
                    'id',
                ),
            ).toBe('15 Agu 2026 10:00');
        });
    });

    it('keeps a 24 hour clock past noon', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(
                formatBrowserDateTime('2026-07-30T10:05:00.000000Z', 'Y-m-d'),
            ).toBe('2026-07-30 17:05');
        });
    });

    it('falls back to the ISO ordering for an unknown preset', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(formatBrowserDateTime(lateEveningUtc, 'r-a-n-d-o-m')).toBe(
                '2026-07-31 05:30',
            );
        });
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

describe('formatRelativeTime', () => {
    /* Anchored so "now" is a fixed instant and the thresholds stay assertable. */
    const now = new Date('2026-07-30T12:00:00.000Z');
    const ago = (seconds: number): string =>
        new Date(now.getTime() - seconds * 1000).toISOString();

    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(now);
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('reads anything under a minute as the present moment', () => {
        expect(formatRelativeTime(ago(30), 'en')).toBe('now');
    });

    it('counts minutes, hours and days as they pass', () => {
        expect(formatRelativeTime(ago(5 * 60), 'en')).toBe('5 minutes ago');
        expect(formatRelativeTime(ago(3 * 3600), 'en')).toBe('3 hours ago');
        expect(formatRelativeTime(ago(2 * 86400), 'en')).toBe('2 days ago');
    });

    it('falls back to a date once a week has passed', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(formatRelativeTime(ago(30 * 86400), 'en')).toBe(
                'Jun 30, 2026',
            );
        });
    });

    it('phrases the distance in the requested locale', () => {
        expect(formatRelativeTime(ago(5 * 60), 'id')).toBe('5 menit yang lalu');
    });

    it('returns a safe fallback for a missing or unparseable instant', () => {
        expect(formatRelativeTime(null, 'en')).toBe(DATE_TIME_FALLBACK);
        expect(formatRelativeTime('', 'en')).toBe(DATE_TIME_FALLBACK);
        expect(formatRelativeTime('not-a-date', 'en')).toBe(DATE_TIME_FALLBACK);
    });
});

describe('formatBrowserTime', () => {
    it('renders the clock in the browser timezone, not UTC', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(formatBrowserTime(lateEveningUtc)).toBe('05:30');
        });

        withTimeZone('UTC', () => {
            expect(formatBrowserTime(lateEveningUtc)).toBe('22:30');
        });
    });

    /* The reason this helper exists: a locale clock would say "5:05 PM" here. */
    it('keeps a 24 hour clock past noon', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            expect(formatBrowserTime('2026-07-30T10:05:00.000000Z')).toBe(
                '17:05',
            );
        });
    });

    it('pads a single digit hour', () => {
        withTimeZone('UTC', () => {
            expect(formatBrowserTime('2026-07-30T09:05:00.000000Z')).toBe(
                '09:05',
            );
        });
    });

    it('returns a safe fallback for a missing or unparseable instant', () => {
        expect(formatBrowserTime(null)).toBe(DATE_TIME_FALLBACK);
        expect(formatBrowserTime('')).toBe(DATE_TIME_FALLBACK);
        expect(formatBrowserTime('not-a-date')).toBe(DATE_TIME_FALLBACK);
    });
});
