<?php

namespace App\Http\Requests\Chat;

use App\Models\Chat\Message;
use App\Settings\ChatSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * A message names either the conversation it belongs to or the recipient it
     * opens one with, never both: the conversation is created lazily by the
     * first valid message.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required_without:recipient_id', 'missing_with:recipient_id', 'uuid'],
            'recipient_id' => ['required_without:conversation_id', 'uuid'],
            /*
             * Multiline is allowed; whitespace-only is not, which `string` alone
             * would accept.
             */
            'body' => ['nullable', 'required_without:image', 'string', 'max:'.Message::MAX_LENGTH],
            'image' => [
                'nullable',
                'required_without:body',
                File::image()
                    ->types(['png', 'jpg', 'jpeg', 'webp'])
                    ->max(Message::IMAGE_MAX_KILOBYTES)
                    ->dimensions(
                        Rule::dimensions()
                            ->maxWidth(Message::IMAGE_MAX_DIMENSION)
                            ->maxHeight(Message::IMAGE_MAX_DIMENSION),
                    ),
                'extensions:png,jpg,jpeg,webp',
            ],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->hasFile('image') && ! app(ChatSettings::class)->imageUploadsEnabled) {
                $validator->errors()->add('image', __('chat.message.image_upload_disabled'));
            }
        }];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $body = $this->input('body');

        if (is_string($body)) {
            $this->merge(['body' => trim($body)]);
        }
    }
}
