<?php

namespace App\Http\Requests\Settings;

use App\Enums\AppLayoutPreset;
use App\Enums\AuthLayoutPreset;
use App\Enums\ColorThemePreset;
use App\Enums\FontPreset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandingSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'companyName' => ['required', 'string', 'max:100'],
            'footerText' => ['nullable', 'string', 'max:500'],
            'authLayout' => ['required', 'string', Rule::enum(AuthLayoutPreset::class)],
            'appLayout' => ['required', 'string', Rule::enum(AppLayoutPreset::class)],
            'colorTheme' => ['required', 'string', Rule::enum(ColorThemePreset::class)],
            'fontPreset' => ['required', 'string', Rule::enum(FontPreset::class)],
        ];
    }
}
