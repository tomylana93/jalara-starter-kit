<?php

namespace App\Mail;

use App\Settings\BrandingSettings;
use App\Settings\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMailConfiguration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly MailSettings $mailSettings,
        private readonly BrandingSettings $brandingSettings,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailSettings->fromAddress, $this->mailSettings->fromName),
            subject: __('setting.mail.test.subject', ['company' => $this->brandingSettings->companyName]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.settings.test',
            with: [
                'companyName' => $this->brandingSettings->companyName,
                'footerText' => $this->brandingSettings->footerText,
                'fromName' => $this->mailSettings->fromName,
                'fromAddress' => $this->mailSettings->fromAddress,
            ],
        );
    }
}
