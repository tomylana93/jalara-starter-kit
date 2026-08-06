<?php

namespace App\Http\Requests\Backups;

use App\Enums\Permission;
use App\Rules\Backups\IsBackupArchive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class UploadBackupRequest extends FormRequest
{
    /**
     * The route group already enforces this permission; repeating it here keeps
     * the request answerable on its own, as every other request in the
     * application is.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageBackups->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The size ceiling is PHP's own, read at runtime: a limit above
     * `upload_max_filesize` or `post_max_size` is never reached, because the
     * request arrives empty and validation reports "required" for a file the
     * operator plainly selected. `IsBackupArchive` is what actually decides
     * whether the ZIP is a backup - the extension and MIME rules only keep the
     * obvious mistakes out of the inspector.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'required',
            'file',
            'extensions:zip',
            'mimetypes:application/zip',
        ];

        /*
         * PHP reports zero when `upload_max_filesize` is unlimited, and `max:0`
         * would reject every file there is. No ceiling is the honest expression
         * of no ceiling; the web server still has the last word.
         */
        $maxKilobytes = (int) (UploadedFile::getMaxFilesize() / 1024);

        if ($maxKilobytes > 0) {
            $rules[] = 'max:'.$maxKilobytes;
        }

        $rules[] = new IsBackupArchive;

        return ['archive' => $rules];
    }
}
