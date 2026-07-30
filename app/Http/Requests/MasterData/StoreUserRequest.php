<?php

namespace App\Http\Requests\MasterData;

use App\Authorization\AuthorizationCatalog;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Creation accepts a name, an email, and one role. The status is not part of
     * the contract: the model default makes every new account active.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(AuthorizationCatalog $catalog): array
    {
        return [
            ...$this->profileRules(),
            'role' => ['required', 'string', Rule::in($catalog->assignableRoleValues())],
            'status' => ['prohibited'],
        ];
    }
}
