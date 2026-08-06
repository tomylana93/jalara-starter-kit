<?php

namespace App\Http\Requests\MasterData;

use App\Models\User;
use App\Rules\MasterData\IsUserImportSheet;
use App\Settings\SettingsResolver;
use App\Settings\UserProvisioningSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Importing is creating, in bulk.
 *
 * It therefore carries the permission creating already carries rather than one
 * of its own: anyone who may add users one at a time may add them faster, and a
 * permission that only slows someone down enforces nothing.
 */
final class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The size ceiling is PHP's own, read at runtime, for the reason
     * `UploadBackupRequest` documents: a limit above `upload_max_filesize`
     * arrives as an empty request and reports "required" for a file the
     * operator plainly selected.
     *
     * An `.xlsx` is a ZIP, and PHP reports it as one about as often as it
     * reports the spreadsheet type, so both are accepted here and
     * `IsUserImportSheet` is what actually decides.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'required',
            'file',
            'extensions:xlsx',
            'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip',
        ];

        $maxKilobytes = (int) (UploadedFile::getMaxFilesize() / 1024);

        if ($maxKilobytes > 0) {
            $rules[] = 'max:'.$maxKilobytes;
        }

        /*
         * Without a default password no row could be provisioned whatever the
         * sheet says, so the sheet is not opened at all. The controller answers
         * that case; parsing a file to reach a foregone conclusion only makes
         * the operator wait for it.
         */
        if ($this->hasDefaultPassword()) {
            $rules[] = new IsUserImportSheet;
        }

        return ['sheet' => $rules];
    }

    /**
     * The uploaded spreadsheet.
     */
    public function sheetPath(): string
    {
        /** @var UploadedFile $sheet */
        $sheet = $this->file('sheet');

        $path = $sheet->getRealPath();

        return $path === false ? $sheet->path() : $path;
    }

    /**
     * Determine whether provisioning can succeed at all.
     */
    public function hasDefaultPassword(): bool
    {
        return SettingsResolver::tryResolve(UserProvisioningSettings::class)?->hasDefaultPassword() ?? false;
    }
}
