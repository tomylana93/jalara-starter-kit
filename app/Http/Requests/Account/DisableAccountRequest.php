<?php

namespace App\Http\Requests\Account;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DisableAccountRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('disableAccount', $user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['password' => $this->currentPasswordRules()];
    }
}
