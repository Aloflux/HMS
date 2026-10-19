<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AfyaDesk') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-brand-900 font-sans text-white antialiased">
        <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center gap-8 px-6 py-16">
            <div>
                <p class="inline-flex items-center gap-2 text-sm uppercase tracking-widest text-brand-200">
                    <x-icon name="bx-plus-medical" class="text-lg" />
                    AfyaDesk
                </p>
                <h1 class="mt-3 text-4xl font-semibold">One record, every service billed.</h1>
                <p class="mt-4 text-lg text-brand-100">Patient registration, queues, clinical care, pharmacy, cashier and Ministry of Health reports for a single facility, on a shared multi-tenant platform.</p>
            </div>
            <div>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-5 py-3 text-sm font-semibold text-brand-900">
                    <x-icon name="bx-log-in" class="text-lg" />
                    Sign in
                </a>
            </div>
        </main>
    </body>
</html>
