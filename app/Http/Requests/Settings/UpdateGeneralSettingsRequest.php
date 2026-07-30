<?php

namespace App\Http\Requests\Settings;

use App\Enums\DateFormat;
use App\Enums\Locale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'applicationName' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'defaultLocale' => ['required', 'string', Rule::enum(Locale::class)],
            'dateFormat' => ['required', 'string', Rule::enum(DateFormat::class)],
        ];
    }
}
