<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'COURRIER GUTF') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('layouts.navigation')

    @isset($header)
        <header class="bg-white border-bottom">
            <div class="container py-3">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main class="container py-4">
        {{ $slot }}
    </main>
    @can('chat.view')
        @vite(['resources/js/chat.js'])
        <x-chat-widget :initial-unread="0" />
    @endcan
    
    // @can('chat.view')
    //    <x-chat-widget :initial-unread="0" />
    // @endcan

</body>
</html>
