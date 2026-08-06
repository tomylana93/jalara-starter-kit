<?php

namespace App\Imports;

use App\Actions\Users\CreateManagedUser;
use App\Data\Users\CreateManagedUserData;
use App\Enums\Role;
use App\Models\User;
use App\Support\Users\UserImportSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Creates users from a spreadsheet, all of them or none.
 *
 * The whole sheet is validated before a single row is written. Two properties
 * follow from that, and both are the point: an operator sees every problem in
 * one pass instead of one problem per upload, and a rejected file leaves the
 * database exactly as it found it - so fixing the file and uploading it again is
 * always safe, which it would not be if a partial import had already claimed
 * some of its emails.
 *
 * Import only ever creates. Updating runs through a policy that is authorized
 * per target user, and a bulk operation authorized once cannot honour that.
 */
final readonly class UsersImport
{
    public function __construct(private CreateManagedUser $createManagedUser) {}

    /**
     * Create every user the sheet describes and return how many were created.
     *
     * @param  list<string>  $assignableRoles  The roles this actor may grant.
     * @return int<0, max>
     *
     * @throws ValidationException When any row is invalid; nothing is written.
     */
    public function handle(string $path, array $assignableRoles): int
    {
        $sheet = UserImportSheet::tryRead($path);

        throw_if(
            ! $sheet instanceof UserImportSheet,
            RuntimeException::class,
            'The import sheet could not be read.',
        );

        $this->validate($sheet, $assignableRoles);

        DB::transaction(function () use ($sheet): void {
            foreach ($sheet->rows as $row) {
                $this->createManagedUser->handle(new CreateManagedUserData(
                    name: $row['name'],
                    email: $row['email'],
                    /* Every surviving value is one of the actor's assignable roles. */
                    role: Role::from($row['role']),
                ));
            }
        });

        return count($sheet->rows);
    }

    /**
     * @param  list<string>  $assignableRoles
     *
     * @throws ValidationException
     */
    private function validate(UserImportSheet $sheet, array $assignableRoles): void
    {
        $validator = Validator::make(
            ['rows' => $sheet->rows],
            [
                'rows' => ['required', 'array', 'min:1', 'max:'.UserImportSheet::MAX_ROWS],
                'rows.*.name' => ['required', 'string', 'max:255'],
                'rows.*.email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    /* Two different duplicates deserve two different answers:
                       one the file collides with itself, one the file collides
                       with an account that already exists. */
                    'distinct:ignore_case',
                    Rule::unique(User::class, 'email'),
                ],
                'rows.*.role' => ['required', 'string', Rule::in($assignableRoles)],
            ],
            attributes: [
                'rows.*.name' => __('master_data.user.label.name'),
                'rows.*.email' => __('master_data.user.label.email'),
                'rows.*.role' => __('master_data.user.label.role'),
            ],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($this->rowMessages($sheet, $validator->errors()));
        }
    }

    /**
     * Restate validation failures against the rows an operator can actually see.
     *
     * The validator counts from zero over the rows that survived blank-row
     * skipping; a spreadsheet counts from one and includes its header. Reporting
     * the validator's index would send someone to the wrong line, which quietly
     * discredits every other message in the list.
     *
     * Only the first message per key is carried: Inertia flattens an error bag
     * the same way, so the rest would be data no client ever renders.
     *
     * @return array<string, string>
     */
    private function rowMessages(UserImportSheet $sheet, MessageBag $errors): array
    {
        $rowMessages = [];

        foreach ($errors->keys() as $key) {
            $segments = explode('.', $key);

            if (count($segments) !== 3) {
                /* A failure about the sheet as a whole, not about one row. */
                $rowMessages[$key] = $errors->first($key);

                continue;
            }

            [, $index, $column] = $segments;
            $line = $sheet->lineNumbers[(int) $index] ?? (int) $index;

            $rowMessages["rows.{$line}.{$column}"] = __('master_data.user.import.validation.row', [
                'row' => $line,
                'message' => $errors->first($key),
            ]);
        }

        return $rowMessages;
    }
}
