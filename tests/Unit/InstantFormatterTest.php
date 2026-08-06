<?php

use App\Support\InstantFormatter;

/**
 * The shared expectations a PDF and the screen both have to satisfy.
 *
 * @return list<array{name: string, instant: ?string, dateFormat: string, timeZone: string, locale: string, expected: string}>
 */
function instantCases(): array
{
    /* Read from the path rather than through base_path(): this contract needs
       no container, and binding one just to find a file would be gratuitous. */
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/instants.json');

    /** @var array{cases: list<array{name: string, instant: ?string, dateFormat: string, timeZone: string, locale: string, expected: string}>} $fixture */
    $fixture = json_decode((string) $contents, true, flags: JSON_THROW_ON_ERROR);

    return $fixture['cases'];
}

dataset('instants', function () {
    foreach (instantCases() as $case) {
        yield $case['name'] => [$case];
    }
});

/*
 * The client renders the same fixture in `resources/js/lib/dateTime.test.ts`.
 * Changing one formatter without the other turns exactly one of the two suites
 * red, which is the only reason this duplication is safe to carry.
 */
it('renders an instant exactly as the browser would', function (array $case) {
    $instant = $case['instant'] === null
        ? null
        : new DateTimeImmutable($case['instant']);

    expect((new InstantFormatter)->format(
        $instant,
        $case['dateFormat'],
        $case['timeZone'],
        $case['locale'],
    ))->toBe($case['expected']);
})->with('instants');
