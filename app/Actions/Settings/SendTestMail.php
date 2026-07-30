<?php

namespace App\Actions\Settings;

use App\Mail\TestMailConfiguration;
use App\Models\User;
use App\Settings\BrandingSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Mail;

final class SendTestMail
{
    /**
     * Send a test message to the authenticated administrator.
     */
    public function handle(User $recipient, MailSettings $mailSettings, BrandingSettings $brandingSettings): void
    {
        Mail::to($recipient->email, $recipient->name)
            ->send(new TestMailConfiguration($mailSettings, $brandingSettings));
    }
}
