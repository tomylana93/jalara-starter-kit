<?php

namespace App\Http\Requests\MasterData;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportUsersRequest extends FormRequest
{
    /**
     * The largest selection the export accepts.
     *
     * Selection is scoped to one server page, so the biggest page size is also
     * the most rows a legitimate request can carry.
     */
    public const int MAX_IDS = 50;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'ids.*' => ['string', 'uuid', 'distinct', 'exists:users,id'],
            /*
             * Only the PDF reads this. A spreadsheet writes native UTC instants
             * and lets the workbook display them, but a document is a picture of
             * what the operator saw, and the browser that renders it runs on the
             * server - so the reader's zone has to travel with the request.
             */
            'timezone' => ['nullable', 'string', 'timezone'],
        ];
    }

    /**
     * The reader's own timezone, falling back to UTC.
     *
     * A client that sends nothing still gets a defensible document rather than
     * one silently stamped in whatever zone the server happens to run in.
     */
    public function timeZone(): string
    {
        $timezone = $this->validated('timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }

    /**
     * The authenticated actor, which authorize() has already established.
     */
    public function actor(): User
    {
        $actor = $this->user();

        throw_if(! $actor instanceof User, AuthorizationException::class);

        return $actor;
    }

    /**
     * The selected user ids, in the order they were picked.
     *
     * @return list<string>
     */
    public function selectedIds(): array
    {
        /** @var list<string> $ids */
        $ids = array_values((array) $this->validated('ids'));

        return $ids;
    }
}
