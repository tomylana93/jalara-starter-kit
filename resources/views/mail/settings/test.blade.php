<x-mail::message>
# {{ __('setting.mail.test.heading') }}

{{ __('setting.mail.test.intro', ['company' => $companyName]) }}

{{ __('setting.mail.test.sender', ['name' => $fromName, 'address' => $fromAddress]) }}

@if ($footerText)
{{ $footerText }}
@endif
</x-mail::message>
