<?php

namespace App\Exports;

use App\Enums\Role;
use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Throwable;

/**
 * A spreadsheet of the users a request selected.
 *
 * The export mirrors what the table already shows and nothing more: no
 * password, token, or other credential material ever reaches the file.
 */
final class UsersExport
{
    /**
     * The zero based column holding the created instant.
     */
    private const int CREATED_AT_COLUMN = 4;

    /**
     * The Excel display format for the created instant.
     */
    private const string DATE_FORMAT = 'yyyy-mm-dd hh:mm:ss';

    /**
     * Breathing room, in characters, added to every measured column.
     */
    private const int WIDTH_PADDING = 2;

    /**
     * Write the selected users to a spreadsheet and return its path.
     *
     * @param  list<string>  $ids
     */
    public function write(array $ids): string
    {
        $path = tempnam(sys_get_temp_dir(), 'users-export');

        throw_if($path === false, RuntimeException::class, 'Unable to create a temporary export file.');

        try {
            $heading = $this->heading();
            /* Rows are materialized first because column widths must be known
               before the writer opens the file, and a selection is capped at
               a single page of rows. */
            $rows = array_values(
                $this->users($ids)->map(fn (User $user): array => $this->row($user))->all(),
            );

            /* The type is explicit so the path from tempnam() can be used as
               it is, leaving no second file behind to leak. */
            $writer = SimpleExcelWriter::create(
                file: $path,
                type: 'xlsx',
                configureWriter: function (WriterInterface $writer) use ($heading, $rows): void {
                    $this->configureColumnWidths($writer, $heading, $rows);
                },
            );

            $writer->addHeader($heading);

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValuesWithStyles($row, columnStyles: [
                    self::CREATED_AT_COLUMN => (new Style)->setFormat(self::DATE_FORMAT),
                ]));
            }

            $writer->close();
        } catch (Throwable $throwable) {
            @unlink($path);

            throw $throwable;
        }

        return $path;
    }

    /**
     * The column titles, localized like the table they came from.
     *
     * @return list<string>
     */
    private function heading(): array
    {
        return [
            __('master_data.user.label.name'),
            __('master_data.user.label.email'),
            __('master_data.user.label.role'),
            __('master_data.user.label.status'),
            __('master_data.user.label.created_at'),
        ];
    }

    /**
     * The selected users, restored to the order they were selected in.
     *
     * @param  list<string>  $ids
     * @return Collection<int, User>
     */
    private function users(array $ids): Collection
    {
        $position = array_flip($ids);

        return User::query()
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->with('roles:id,name')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (User $user): int => $position[$user->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: ?DateTimeInterface}
     */
    private function row(User $user): array
    {
        return [
            $user->name,
            $user->email,
            $this->role($user) ?? __('master_data.user.role_missing'),
            $user->status->label(),
            /* A native date/time cell, still carrying the UTC instant the
               table sends, so the workbook can sort and filter on it. */
            $user->created_at === null
                ? null
                : DateTimeImmutable::createFromInterface($user->created_at)
                    ->setTimezone(new DateTimeZone('UTC')),
        ];
    }

    /**
     * Present a single role, most privileged first, as the table does.
     */
    private function role(User $user): ?string
    {
        foreach (Role::cases() as $role) {
            if ($user->roles->contains('name', $role->value)) {
                return $role->label();
            }
        }

        return null;
    }

    /**
     * Size every column to the longest value it actually carries.
     *
     * OpenSpout has no autosize, so the widths are measured from the exported
     * values themselves and handed to the writer before the file is opened.
     *
     * @param  list<string>  $heading
     * @param  list<array{0: string, 1: string, 2: string, 3: string, 4: ?DateTimeInterface}>  $rows
     */
    private function configureColumnWidths(WriterInterface $writer, array $heading, array $rows): void
    {
        if (! $writer instanceof Writer) {
            return;
        }

        $options = $writer->getOptions();

        foreach ($heading as $column => $title) {
            $width = Str::length($title);

            foreach ($rows as $row) {
                $width = max($width, Str::length($this->displayValue($row[$column])));
            }

            $options->setColumnWidth((float) ($width + self::WIDTH_PADDING), $column + 1);
        }
    }

    /**
     * The text a reader sees for a cell, used only to measure column width.
     */
    private function displayValue(string|DateTimeInterface|null $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            default => $value,
        };
    }
}
