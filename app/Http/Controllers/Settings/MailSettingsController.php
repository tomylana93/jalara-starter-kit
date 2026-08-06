<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\SendTestMail;
use App\Actions\Settings\UpdateMailSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateMailSettingsRequest;
use App\Settings\BrandingSettings;
use App\Settings\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailSettingsController extends Controller
{
    /**
     * Show the mail settings page.
     */
    public function edit(MailSettings $settings): Response
    {
        return Inertia::render('settings/Mail', [
            'settings' => [
                'fromName' => $settings->fromName,
                'fromAddress' => $settings->fromAddress,
            ],
        ]);
    }

    /**
     * Update the mail settings.
     */
    public function update(
        UpdateMailSettingsRequest $request,
        MailSettings $settings,
        UpdateMailSettings $updateMailSettings,
    ): RedirectResponse {
        $updateMailSettings->handle($settings, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.mail.message.updated')]);

        return to_route('settings.mail.edit');
    }

    /**
     * Send a test message to the authenticated administrator.
     */
    public function test(
        Request $request,
        MailSettings $mailSettings,
        BrandingSettings $brandingSettings,
        SendTestMail $sendTestMail,
    ): RedirectResponse {
        $sendTestMail->handle($request->user(), $mailSettings, $brandingSettings);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.mail.test.sent')]);

        return to_route('settings.mail.edit');
    }
}
