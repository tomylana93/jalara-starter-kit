import { vi } from 'vitest';

/**
 * Run assertions with `Intl` pinned to a fixed timezone.
 *
 * `formatBrowserDateTime` deliberately passes no `timeZone` option so the
 * runtime zone applies, which is the behaviour under test. The suite therefore
 * has to choose that zone itself: relying on the machine's own clock makes a
 * test pass or fail on where it runs, and a globally configured zone hides the
 * dependency from the test that needs it.
 */
export const withTimeZone = <T>(timeZone: string, assert: () => T): T => {
    const OriginalDateTimeFormat = Intl.DateTimeFormat;

    /*
     * A function expression, not an arrow: the production code reaches
     * `Intl.DateTimeFormat` through `new`, and an arrow function is not
     * constructible. Returning an object from a constructor call replaces the
     * instance, so the real formatter is what the caller receives.
     */
    const spy = vi.spyOn(Intl, 'DateTimeFormat').mockImplementation(function (
        locales?: Intl.LocalesArgument,
        options?: Intl.DateTimeFormatOptions,
    ) {
        return new OriginalDateTimeFormat(locales, { ...options, timeZone });
    } as unknown as typeof Intl.DateTimeFormat);

    try {
        return assert();
    } finally {
        spy.mockRestore();
    }
};

/**
 * A DST-free zone seven hours ahead of UTC, so a late-evening UTC instant
 * observably lands on the following calendar day.
 */
export const TEST_TIME_ZONE = 'Asia/Jakarta';
