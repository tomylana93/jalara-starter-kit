<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateChatSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateChatSettingsRequest;
use App\Settings\ChatSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChatSettingsController extends Controller
{
    /**
     * Show the chat settings page.
     */
    public function edit(ChatSettings $settings): Response
    {
        return Inertia::render('settings/Chat', [
            'settings' => [
                'chatEnabled' => $settings->chatEnabled,
                'imageUploadsEnabled' => $settings->imageUploadsEnabled,
            ],
        ]);
    }

    /**
     * Update the chat settings.
     */
    public function update(
        UpdateChatSettingsRequest $request,
        ChatSettings $settings,
        UpdateChatSettings $updateChatSettings,
    ): RedirectResponse {
        $updateChatSettings->handle($settings, $request->toData($settings));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.chat.message.updated')]);

        return to_route('settings.chat.edit');
    }
}
