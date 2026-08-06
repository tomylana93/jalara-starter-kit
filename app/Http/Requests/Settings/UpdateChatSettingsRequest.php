<?php

namespace App\Http\Requests\Settings;

use App\Data\Settings\UpdateChatSettingsData;
use App\Settings\ChatSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChatSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chatEnabled' => ['required', 'boolean'],
            'imageUploadsEnabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The validated settings, with both toggles resolved to a definite value.
     *
     * The image toggle is `sometimes`: the form omits it entirely while chat is
     * off, and an omitted toggle means "leave it as it is" rather than "turn it
     * off". Resolving that here keeps the action's input total, so it never has
     * to ask what the current value was.
     */
    public function toData(ChatSettings $settings): UpdateChatSettingsData
    {
        return new UpdateChatSettingsData(
            chatEnabled: $this->boolean('chatEnabled'),
            imageUploadsEnabled: $this->has('imageUploadsEnabled')
                ? $this->boolean('imageUploadsEnabled')
                : $settings->imageUploadsEnabled,
        );
    }
}
