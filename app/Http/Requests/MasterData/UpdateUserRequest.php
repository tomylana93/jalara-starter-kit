<?php

namespace App\Http\Requests\MasterData;

use App\Authorization\AuthorizationCatalog;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        $target = $this->route('user');

        if (! $target instanceof User) {
            return false;
        }

        return $this->user()?->can('update', $target) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(AuthorizationCatalog $catalog): array
    {
        $target = $this->route('user');

        return [
            ...$this->profileRules($target instanceof User ? $target->id : null),
            'status' => ['required', 'string', Rule::enum(UserStatus::class)],
            'role' => ['required', 'string', Rule::in($catalog->assignableRoleValues())],
        ];
    }
}
