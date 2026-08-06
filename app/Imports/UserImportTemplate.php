<?php

namespace App\Imports;

use App\Support\Users\UserImportSheet;
use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Throwable;

/**
 * The blank sheet an operator starts from.
 *
 * It is generated rather than committed so the header row cannot drift from the
 * parser that reads it back: both come from `UserImportSheet::COLUMNS`. A
 * checked-in file would keep describing an older contract until someone noticed
 * that the official template no longer imports.
 */
final class UserImportTemplate
{
    /**
     * Write the template and return its path.
     *
     * The example row carries a role the downloading actor may actually grant,
     * so the contract someone learns from the file already matches what they
     * are allowed to do.
     *
     * @param  list<string>  $assignableRoles
     */
    public function write(array $assignableRoles): string
    {
        $path = tempnam(sys_get_temp_dir(), 'users-import-template');

        throw_if($path === false, RuntimeException::class, 'Unable to create a temporary template file.');

        try {
            $writer = SimpleExcelWriter::create(file: $path, type: 'xlsx');

            $writer->addHeader(UserImportSheet::COLUMNS);
            $writer->addRow([
                __('master_data.user.placeholder.name'),
                __('master_data.user.placeholder.email'),
                $assignableRoles[0] ?? '',
            ]);

            $writer->close();
        } catch (Throwable $throwable) {
            @unlink($path);

            throw $throwable;
        }

        return $path;
    }
}
