<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AfyaDesk') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 font-sans text-slate-900 antialiased">
        @if ($plain)
            {{ $slot }}
        @else
        <div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0">
            <div>
                <a href="/" class="inline-flex items-center gap-2 text-brand-800">
                    <x-icon name="bx-plus-medical" class="text-4xl text-brand-700" />
                    <span class="text-2xl font-semibold">AfyaDesk</span>
                </a>
            </div>

            <div class="mt-6 w-full {{ $maxWidth }} overflow-hidden rounded-lg bg-white px-6 py-6 shadow-sm">
                {{ $slot }}
            </div>
        </div>
        @endif
    </body>
</html>
