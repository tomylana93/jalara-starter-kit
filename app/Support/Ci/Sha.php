<?php

namespace App\Support\Ci;

use Illuminate\Support\Str;

/**
 * The one place that knows what a commit sha looks like and how it is shown.
 *
 * Release decisions address commits by full sha and nothing else — an
 * abbreviation is ambiguous and a ref can move — so validating that a value
 * really is one belongs here rather than repeated as a literal pattern at every
 * boundary that reads one.
 */
final class Sha
{
    /**
     * The length git writes and GitHub's API returns.
     */
    public const int LENGTH = 40;

    /**
     * How many characters are enough to recognise a commit in a message.
     */
    public const int SHORT_LENGTH = 12;

    private const string PATTERN = '/^[0-9a-f]{'.self::LENGTH.'}$/';

    public static function isFull(string $sha): bool
    {
        return Str::isMatch(self::PATTERN, $sha);
    }

    public static function short(string $sha): string
    {
        return Str::substr($sha, 0, self::SHORT_LENGTH);
    }
}
