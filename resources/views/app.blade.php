@php
    $branding = array_merge(
        \App\Http\Presenters\BrandingPresenter::defaults(),
        (array) data_get($page, 'props.branding', []),
    );
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-color-theme="{{ $branding['colorTheme'] }}"
    data-font-preset="{{ $branding['fontPreset'] }}"
    @class(['dark' => ($appearance ?? 'system') == 'dark'])
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{--
            The uploaded icon wins when one is stored, with the light variant
            covering both modes whenever no dark variant exists. The bundled
            files stay as the fallback so there is always a favicon.
        --}}
        @if ($branding['iconUrl'])
            <link rel="icon" href="{{ $branding['iconUrl'] }}" media="(prefers-color-scheme: light)">
            <link rel="icon" href="{{ $branding['iconDarkUrl'] ?: $branding['iconUrl'] }}" media="(prefers-color-scheme: dark)">
            <link rel="apple-touch-icon" href="{{ $branding['iconUrl'] }}">
        @else
            <link rel="icon" href="/assets/images/branding/favicon.ico" sizes="any">
            <link rel="icon" href="/assets/images/branding/icon.png" type="image/png" media="(prefers-color-scheme: light)">
            <link rel="icon" href="/assets/images/branding/icon-dark.png" type="image/png" media="(prefers-color-scheme: dark)">
            <link rel="apple-touch-icon" href="/assets/images/branding/icon.png">
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
