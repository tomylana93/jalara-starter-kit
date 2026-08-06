<?php

namespace App\Support\Backups;

use ZipArchive;

/**
 * The entries of one archive, checked against what a backup may contain.
 *
 * A restore executes the SQL it finds and copies the files it finds over the
 * project root, so "is this a backup archive?" is a security question, not a
 * convenience one: an archive carrying `storage/framework/views/x.php` would
 * turn the `manage backups` permission into arbitrary code execution. The answer
 * is an allowlist, and it is deliberately strict - one unrecognised entry
 * rejects the whole archive rather than being skipped, because an archive this
 * application did not produce is not a backup of it.
 *
 * The allowed file prefixes are derived from the backup configuration rather
 * than repeated here, so widening what is archived widens what may be restored
 * in exactly the same commit.
 */
final readonly class BackupArchiveContents
{
    /** Where Spatie writes database dumps inside the archive. */
    public const string DUMP_PREFIX = 'db-dumps/';

    /**
     * @param  list<string>  $dumpEntries  every file under `db-dumps/`, whatever its extension
     * @param  list<string>  $fileEntries  every file under a configured media prefix
     */
    private function __construct(
        public array $dumpEntries,
        public array $fileEntries,
    ) {}

    /**
     * Read the entries of a local archive, or null when it is not one of ours.
     *
     * Null covers every rejection - unopenable, empty, or carrying a path
     * outside the allowlist - because the caller can do nothing different with
     * the distinction, and a validation message that enumerates what a forged
     * archive got wrong only helps forge the next one.
     */
    public static function tryRead(string $path): ?self
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return null;
        }

        $prefixes = self::allowedFilePrefixes();
        $dumpEntries = [];
        $fileEntries = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->getNameIndex($index);

                if ($entry === false || self::isTraversal($entry)) {
                    return null;
                }

                /*
                 * Directory entries carry no content. They are accepted when
                 * they lie on the path to something allowed, and never
                 * extracted: extraction lists the file entries explicitly.
                 */
                if (str_ends_with($entry, '/')) {
                    if (! self::isAllowedDirectory($entry, $prefixes)) {
                        return null;
                    }

                    continue;
                }

                if (str_starts_with($entry, self::DUMP_PREFIX)) {
                    $dumpEntries[] = $entry;

                    continue;
                }

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($entry, $prefix)) {
                        $fileEntries[] = $entry;

                        continue 2;
                    }
                }

                return null;
            }
        } finally {
            $zip->close();
        }

        /* An archive of nothing is not an archive of this application. */
        if ($dumpEntries === [] && $fileEntries === []) {
            return null;
        }

        return new self($dumpEntries, $fileEntries);
    }

    /**
     * Every entry a restore extracts, in the order it should be applied.
     *
     * @return list<string>
     */
    public function extractableEntries(): array
    {
        return [...$this->dumpEntries, ...$this->fileEntries];
    }

    /**
     * The media prefixes of the archive, relative to the project root.
     *
     * @return list<string>
     */
    private static function allowedFilePrefixes(): array
    {
        /** @var array<int, string> $include */
        $include = (array) config('backup.backup.source.files.include', []);
        $root = rtrim((string) config('backup.backup.source.files.relative_path', base_path()), '/').'/';

        return array_values(array_filter(array_map(
            function (string $path) use ($root): ?string {
                if (! str_starts_with($path, $root)) {
                    return null;
                }

                return rtrim(mb_substr($path, mb_strlen($root)), '/').'/';
            },
            $include,
        )));
    }

    /**
     * A directory entry is allowed when it is inside an allowed prefix or is one
     * of the parents leading to it (`storage/`, `storage/app/`).
     *
     * @param  list<string>  $prefixes
     */
    private static function isAllowedDirectory(string $entry, array $prefixes): bool
    {
        if (str_starts_with(self::DUMP_PREFIX, $entry) || str_starts_with($entry, self::DUMP_PREFIX)) {
            return true;
        }

        return array_any($prefixes, fn (string $prefix) => str_starts_with($prefix, $entry) || str_starts_with($entry, $prefix));
    }

    /**
     * Absolute paths, parent segments and Windows separators never appear in an
     * archive this application wrote, and each is a way out of the project root.
     */
    private static function isTraversal(string $entry): bool
    {
        return str_starts_with($entry, '/')
            || str_contains($entry, '\\')
            /* Any segment, not just `../`: a trailing `..` climbs just as well. */
            || in_array('..', explode('/', $entry), true);
    }
}
