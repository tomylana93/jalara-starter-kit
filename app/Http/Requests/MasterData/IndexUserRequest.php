<?php

namespace App\Http\Requests\MasterData;

use App\Models\User;
use App\Tables\TableQuery;
use App\Tables\UsersTable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
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
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', Rule::in(array_keys(UsersTable::SORTABLE))],
            'direction' => ['nullable', 'string', Rule::in(TableQuery::DIRECTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', Rule::in(TableQuery::PER_PAGE_OPTIONS)],
        ];
    }
}
