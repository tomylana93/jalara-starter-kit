<?php

namespace App\Rules\MasterData;

use App\Support\Users\UserImportSheet;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts only a spreadsheet shaped like a user import.
 *
 * An `.xlsx` file is a ZIP archive, so the extension and the reported MIME type
 * are both satisfied by anything zipped. What the file actually is only becomes
 * knowable by opening it, and the shape of its header row is what decides
 * whether the rows behind it mean anything at all.
 */
final class IsUserImportSheet implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('master_data.user.import.validation.unreadable')->translate();

            return;
        }

        $path = $value->getRealPath();

        if ($path === false) {
            $fail('master_data.user.import.validation.unreadable')->translate();

            return;
        }

        $sheet = UserImportSheet::tryRead($path);

        if (! $sheet instanceof UserImportSheet) {
            $fail('master_data.user.import.validation.unreadable')->translate();

            return;
        }

        $missing = $sheet->missingColumns();

        if ($missing !== []) {
            $fail('master_data.user.import.validation.columns')
                ->translate(['columns' => implode(', ', $missing)]);

            return;
        }

        if ($sheet->rows === []) {
            $fail('master_data.user.import.validation.empty')->translate();

            return;
        }

        if (count($sheet->rows) > UserImportSheet::MAX_ROWS) {
            $fail('master_data.user.import.validation.too_many_rows')
                ->translate(['max' => UserImportSheet::MAX_ROWS]);
        }
    }
}
