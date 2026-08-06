<?php

namespace App\Http\Requests\Account;

use App\Concerns\ProfileValidationRules;
use App\Data\Account\UpdateProfileData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }

    /**
     * The validated profile attributes.
     */
    public function toData(): UpdateProfileData
    {
        return new UpdateProfileData(
            name: (string) $this->validated('name'),
            email: (string) $this->validated('email'),
        );
    }
}
