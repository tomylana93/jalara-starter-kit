<?php

namespace App\Support\Users;

use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Throwable;

/**
 * A user import spreadsheet, read into rows that carry their own line numbers.
 *
 * The header row is matched by machine key rather than by the localized labels
 * the export writes: a sheet must stay readable after the application locale
 * changes, and `admin` means the same thing in every language.
 */
final readonly class UserImportSheet
{
    /**
     * The columns a sheet has to carry.
     *
     * Shared with the template writer so the file handed to an operator and the
     * parser reading it back can never describe different contracts.
     *
     * @var list<string>
     */
    public const array COLUMNS = ['name', 'email', 'role'];

    /**
     * The most rows one import accepts.
     *
     * This is a time budget rather than a round number: every row costs one
     * password hash, which is deliberately slow, so the ceiling is what keeps a
     * synchronous import comfortably inside a web request.
     */
    public const int MAX_ROWS = 100;

    /**
     * The spreadsheet row the first record sits on, the header taking row 1.
     */
    private const int FIRST_DATA_ROW = 2;

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $rows
     * @param  list<int>  $lineNumbers  The spreadsheet row each entry of $rows came from.
     */
    private function __construct(
        public array $headers,
        public array $rows,
        public array $lineNumbers,
    ) {}

    /**
     * Read a sheet, or return null when the file is not a readable spreadsheet.
     *
     * A missing column is not a read failure: the caller reports that far more
     * usefully than "this file is unreadable" can, so the rows are still built
     * from whichever columns are present.
     */
    public static function tryRead(string $path): ?self
    {
        try {
            /*
             * Empty rows are preserved so the reader's sequence stays the
             * spreadsheet's own. Without this the reader simply omits them, and
             * every line number after a blank row would point an operator at
             * the wrong record - which quietly discredits the whole report.
             */
            $reader = SimpleExcelReader::create($path, 'xlsx')->preserveEmptyRows();

            $headers = array_values(array_map(
                self::normalizeHeader(...),
                $reader->getHeaders() ?? [],
            ));

            $rows = [];
            $lineNumbers = [];
            $line = self::FIRST_DATA_ROW;

            foreach ($reader->getRows() as $row) {
                $values = self::values($row);

                /* Trailing blank rows are what spreadsheets leave behind; they
                   are absence, not a record missing every field. */
                if (! self::isBlank($values)) {
                    $rows[] = $values;
                    $lineNumbers[] = $line;
                }

                $line++;
            }
        } catch (Throwable $throwable) {
            /*
             * The operator only ever sees "this file could not be read", which
             * is all they can act on. Whoever has to explain why needs the
             * reader's own message, so it goes to the log rather than nowhere.
             */
            Log::warning('Failed to read a user import sheet.', [
                'exception' => $throwable->getMessage(),
            ]);

            return null;
        }

        return new self($headers, $rows, $lineNumbers);
    }

    /**
     * The required columns the header row does not carry.
     *
     * @return list<string>
     */
    public function missingColumns(): array
    {
        return array_values(array_diff(self::COLUMNS, $this->headers));
    }

    /**
     * Reduce one raw row to the required columns, as trimmed strings.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private static function values(array $row): array
    {
        $normalized = [];

        foreach ($row as $header => $value) {
            $normalized[self::normalizeHeader((string) $header)] = self::scalar($value);
        }

        $values = [];

        foreach (self::COLUMNS as $column) {
            $values[$column] = $normalized[$column] ?? '';
        }

        return $values;
    }

    /**
     * The text of a cell, for the columns this import reads.
     *
     * Every column here is textual, so a cell holding anything else is not a
     * value with a different type - it is a value in the wrong column, and an
     * empty string lets validation say so in the operator's own terms.
     */
    private static function scalar(mixed $value): string
    {
        return match (true) {
            $value === null, is_bool($value) => '',
            $value instanceof DateTimeInterface => '',
            is_scalar($value) => trim((string) $value),
            default => '',
        };
    }

    /**
     * @param  array<string, string>  $values
     */
    private static function isBlank(array $values): bool
    {
        return array_all($values, fn (string $value) => $value === '');
    }

    /**
     * Match a header however it was capitalized or padded.
     */
    private static function normalizeHeader(string $header): string
    {
        return Str::lower(trim($header));
    }
}
