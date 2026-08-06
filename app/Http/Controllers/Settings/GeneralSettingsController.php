<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\DateFormat;
use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    /**
     * Show the general settings page.
     */
    public function edit(GeneralSettings $settings): Response
    {
        return Inertia::render('settings/General', [
            'settings' => [
                'applicationName' => $settings->applicationName,
                'description' => $settings->description,
                'defaultLocale' => $settings->defaultLocale->value,
                'dateFormat' => $settings->dateFormat->value,
            ],
            'localeOptions' => Locale::options(),
            'dateFormatOptions' => DateFormat::options(),
        ]);
    }

    /**
     * Update the general settings.
     */
    public function update(
        UpdateGeneralSettingsRequest $request,
        GeneralSettings $settings,
        UpdateGeneralSettings $updateGeneralSettings,
    ): RedirectResponse {
        $updateGeneralSettings->handle($settings, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('setting.general.message.updated')]);

        return to_route('settings.general.edit');
    }
}
