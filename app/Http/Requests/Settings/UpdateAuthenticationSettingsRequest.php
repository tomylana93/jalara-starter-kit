<?php

namespace App\Http\Requests\Settings;

use App\Enums\PasswordPolicy;
use App\Rules\Settings\StoredDefaultPasswordSatisfiesPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthenticationSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requireEmailVerification' => ['required', 'boolean'],
            'passwordPolicy' => [
                'required',
                'string',
                Rule::enum(PasswordPolicy::class),
                new StoredDefaultPasswordSatisfiesPolicy,
            ],
            'sessionLifetimeMinutes' => ['required', 'integer', 'min:5', 'max:10080'],
        ];
    }
}
