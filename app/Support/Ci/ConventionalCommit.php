<?php

namespace App\Support\Ci;

use Illuminate\Support\Str;

/**
 * The Conventional Commit contract that Release Please reads to derive the next
 * version and the changelog.
 *
 * Pull-request titles are the unit of release under squash merges, so this is
 * applied to titles rather than to the commits inside a pull request.
 */
final class ConventionalCommit
{
    public const string PATTERN = '/^(feat|fix|perf|refactor|docs|test|build|ci|chore|revert)(\([a-z0-9][a-z0-9._\/-]*\))?!?: .+/';

    public const string USAGE = 'Use: <type>(<optional scope>): <english description>. Allowed types: feat, fix, perf, refactor, docs, test, build, ci, chore, revert.';

    public static function isValid(string $subject): bool
    {
        return Str::isMatch(self::PATTERN, $subject);
    }
}
