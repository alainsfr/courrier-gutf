@php
    $brand = $brand ?? [];
    $appLabel = $brand['app_name'] ?? config('app.name', 'COURRIER GUTF');
    $tagline = $brand['app_tagline'] ?? ($brand['ministry'] ?? null);
    $primary = $brand['primary_color'] ?? '#17384b';
    $accent = $brand['accent_color'] ?? '#dc633a';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appLabel }}</title>
        @if (! empty($brandFaviconUrl))
            <link rel="icon" href="{{ $brandFaviconUrl }}">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --gutf-primary: {{ $primary }};
                --gutf-accent: {{ $accent }};
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="{{ url('/') }}" class="no-underline">
                    <x-brand-mark
                        :logo-url="$brandLogoUrl ?? null"
                        :label="$appLabel"
                        :tagline="$tagline"
                        :primary="$primary"
                    />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
