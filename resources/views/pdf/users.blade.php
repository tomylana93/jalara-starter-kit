{{--
    The printed user document.

    The identity block lives here, in the body, rather than in a running header:
    header and footer views do not inherit this view's CSS, so anything put
    there would need the whole stylesheet inlined a second time. A selection is
    capped at one page of rows, so repeating the logo on every page would be
    noise rather than orientation.

    The stylesheet arrives as data rather than being looked up here, so the
    template stays a template. Why it is inlined at all is explained in
    App\Support\Documents\DocumentStylesheet.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <title>{{ $title }}</title>
        <style>
            {!! $stylesheet !!}
        </style>
    </head>
    <body>
        <header class="mb-6 flex items-start justify-between gap-6 border-b border-rule pb-4">
            <div>
                @if ($logoPath !== null)
                    <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="max-h-12" />
                @else
                    <p class="text-base font-semibold">{{ $companyName }}</p>
                @endif

                <h1 class="mt-2 text-lg font-semibold">{{ $title }}</h1>
            </div>

            <dl class="text-right text-muted-ink">
                <dt class="font-medium">{{ __('master_data.user.document.generated_at') }}</dt>
                <dd>{{ $generatedAt }}</dd>
                <dt class="mt-1 font-medium">{{ __('master_data.user.document.generated_by') }}</dt>
                <dd>{{ $generatedBy }}</dd>
            </dl>
        </header>

        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="border-b border-rule">
                    @foreach ($headings as $heading)
                        <th class="py-2 pr-3 font-semibold">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-b border-rule">
                        <td class="py-1.5 pr-3">{{ $row['name'] }}</td>
                        <td class="py-1.5 pr-3">{{ $row['email'] }}</td>
                        <td class="py-1.5 pr-3">{{ $row['role'] }}</td>
                        <td class="py-1.5 pr-3">{{ $row['status'] }}</td>
                        <td class="py-1.5 whitespace-nowrap">{{ $row['createdAt'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
