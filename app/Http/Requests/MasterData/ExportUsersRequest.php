<?php

namespace App\Http\Requests\MasterData;

use App\Models\User;
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
        ];
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
