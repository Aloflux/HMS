<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="idle-timeout" content="{{ config('hms.idle_timeout_minutes') }}">

        <title>{{ config('app.name', 'AfyaDesk') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div x-data="{ sidebar: false }" class="min-h-screen">
            @include('layouts.navigation')

            <div class="lg:ps-64">
                <header class="no-print sticky top-0 z-20 border-b border-slate-200 bg-white/95">
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                        <div class="flex items-center gap-3">
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-sm lg:hidden" @click="sidebar = !sidebar">
                                <x-icon name="bx-menu" class="text-lg" />
                                Menu
                            </button>
                            <div>
                                <p class="text-sm font-semibold text-brand-800">{{ config('app.name') }}</p>
                                <p class="text-xs text-slate-500">{{ $currentFacility->name ?? 'Platform' }}</p>
                            </div>
                        </div>
                        <x-dropdown align="right" width="w-56" contentClasses="bg-white py-1">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-100">
                                    <x-icon name="bx-user-circle" class="text-2xl text-brand-700" />
                                    <span class="hidden max-w-40 truncate sm:inline">{{ auth()->user()->name }}</span>
                                    <x-icon name="bx-chevron-down" class="text-lg text-slate-400" />
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
                                </div>
                                <x-dropdown-link :href="route('profile.edit')">
                                    <x-icon name="bx-user" class="text-lg text-brand-700" />
                                    Profile
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-slate-700 hover:bg-slate-50">
                                        <x-icon name="bx-log-out" class="text-lg text-slate-500" />
                                        Sign out
                                    </button>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                @isset($header)
                    <div class="border-b border-slate-200 bg-white">
                        <div class="px-4 py-5 sm:px-6">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-4 flex items-start gap-2 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900">
                            <x-icon name="bx-check-circle" class="mt-0.5 text-lg" />
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 flex items-start gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">
                            <x-icon name="bx-error-circle" class="mt-0.5 text-lg" />
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 flex items-start gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">
                            <x-icon name="bx-error-circle" class="mt-0.5 text-lg" />
                            <div>
                            <ul class="list-disc ps-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <form id="idle-logout" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
    </body>
</html>
