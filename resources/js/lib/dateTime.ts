/**
 * Rendering of UTC instants in the viewer's own timezone.
 *
 * Timestamps cross the Inertia boundary as UTC ISO 8601 strings, so the browser
 * is the only place that knows which calendar day an instant falls on. No
 * `timeZone` option is ever passed to `Intl`, which is what makes the runtime
 * zone apply.
 */

/** Shown when there is no instant, or the string is not a date. */
export const DATE_TIME_FALLBACK = '—';

/*
 * Numeric parts are read through a fixed locale so the digits and padding stay
 * stable regardless of the browser's language; only the timezone is inherited.
 */
const NUMERIC_LOCALE = 'en-GB';

type InstantParts = {
    year: string;
    month: string;
    day: string;
    hour: string;
    minute: string;
};

const partsOf = (
    date: Date,
    locale: string,
    month: '2-digit' | 'short',
): InstantParts => {
    const formatted = new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month,
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date);

    const partValue = (type: Intl.DateTimeFormatPartTypes): string =>
        formatted.find((part) => part.type === type)?.value ?? '';

    return {
        year: partValue('year'),
        month: partValue('month'),
        day: partValue('day'),
        hour: partValue('hour'),
        minute: partValue('minute'),
    };
};

/**
 * Format a UTC ISO 8601 instant as a browser-local date and time.
 *
 * `dateFormat` carries one of the configured `DateFormat` presets; an
 * unrecognized preset falls back to the ISO ordering. The month name of the
 * `d M Y` preset follows `locale` so it reads naturally in either language.
 */
export const formatBrowserDateTime = (
    value: string | null | undefined,
    dateFormat: string,
    locale: string = NUMERIC_LOCALE,
): string => {
    if (typeof value !== 'string' || value === '') {
        return DATE_TIME_FALLBACK;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return DATE_TIME_FALLBACK;
    }

    const usesMonthName = dateFormat === 'd M Y';
    const { year, month, day, hour, minute } = partsOf(
        date,
        usesMonthName ? locale : NUMERIC_LOCALE,
        usesMonthName ? 'short' : '2-digit',
    );
    const time = `${hour}:${minute}`;

    switch (dateFormat) {
        case 'd/m/Y':
            return `${day}/${month}/${year} ${time}`;
        case 'm/d/Y':
            return `${month}/${day}/${year} ${time}`;
        case 'd M Y':
            return `${day} ${month} ${year} ${time}`;
        default:
            return `${year}-${month}-${day} ${time}`;
    }
};
