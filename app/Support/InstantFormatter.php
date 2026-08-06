<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;

/**
 * Renders a UTC instant exactly as the browser would.
 *
 * Everywhere else in this application the browser owns the timezone: instants
 * cross the Inertia boundary as UTC ISO 8601 and `formatBrowserDateTime` turns
 * them into local text. A PDF has no browser to defer to - Chromium runs on the
 * server, in the server's zone - so the zone arrives as an explicit input and
 * the formatting happens here instead.
 *
 * That makes this a deliberate second implementation of one rule, in a second
 * language. Both sides read from `tests/Fixtures/instants.json`, which is what
 * keeps them from drifting: an expectation cannot be changed on one side alone.
 */
final class InstantFormatter
{
    /**
     * Shown when there is no instant, matching `DATE_TIME_FALLBACK`.
     */
    public const string FALLBACK = '—';

    /**
     * The one preset whose month is a name rather than a number.
     */
    private const string MONTH_NAME_FORMAT = 'd M Y';

    /**
     * Format an instant as local date and time text.
     *
     * `$dateFormat` carries one of the configured `DateFormat` presets; an
     * unrecognized preset falls back to the ISO ordering, as the client does.
     * The clock is always 24 hour, matching the client's `hourCycle: 'h23'`.
     */
    public function format(
        ?DateTimeInterface $instant,
        string $dateFormat,
        string $timeZone,
        string $locale,
    ): string {
        if (! $instant instanceof DateTimeInterface) {
            return self::FALLBACK;
        }

        $zoned = DateTimeImmutable::createFromInterface($instant)
            ->setTimezone(new DateTimeZone($timeZone));

        $day = $zoned->format('d');
        $year = $zoned->format('Y');
        $time = $zoned->format('H:i');

        $month = $dateFormat === self::MONTH_NAME_FORMAT
            ? $this->shortMonth($zoned, $locale)
            : $zoned->format('m');

        return match ($dateFormat) {
            'd/m/Y' => "{$day}/{$month}/{$year} {$time}",
            'm/d/Y' => "{$month}/{$day}/{$year} {$time}",
            self::MONTH_NAME_FORMAT => "{$day} {$month} {$year} {$time}",
            default => "{$year}-{$month}-{$day} {$time}",
        };
    }

    /**
     * The abbreviated month name, read from the same CLDR data as the browser.
     *
     * `Intl` in the browser and `IntlDateFormatter` here are both ICU, which is
     * why "Agu" comes out of both for Indonesian rather than only out of one.
     */
    private function shortMonth(DateTimeImmutable $instant, string $locale): string
    {
        $formatter = new IntlDateFormatter(
            $locale,
            timezone: $instant->getTimezone(),
            pattern: 'MMM',
        );

        $month = $formatter->format($instant);

        /* ICU answers false for a locale it cannot serve at all. */
        return $month === false ? $instant->format('M') : $month;
    }
}
