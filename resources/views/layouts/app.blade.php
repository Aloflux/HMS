<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="idle-timeout" content="{{ config('hms.idle_timeout_minutes') }}">

        @php($pageTitle = \App\Support\Navigation::titleFor(request()->route()?->getName()))
        <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ config('app.name', 'AfyaDesk') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div x-data="{ sidebar: false }" class="min-h-screen">
            <div x-show="sidebar" x-cloak class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden" @click="sidebar = false"></div>
            @include('layouts.navigation')

            <div class="lg:ps-64">
                <header class="no-print sticky top-0 z-20 bg-slate-100/95 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm lg:hidden" @click="sidebar = !sidebar">
                            <x-icon name="bx-menu" class="text-xl" />
                            <span class="sr-only">Menu</span>
                        </button>
                        @if (auth()->user()->permits('patients.view'))
                            <form method="GET" action="{{ route('patients.index') }}" class="min-w-0 flex-1 md:max-w-md">
                                <label class="relative block">
                                    <span class="sr-only">Search patients</span>
                                    <x-icon name="bx-search" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" />
                                    <input type="search" name="q" value="{{ request()->routeIs('patients.index') ? request('q') : '' }}" placeholder="Search patients" class="w-full rounded-full border-slate-200 bg-white py-2 ps-10 pe-4 text-sm shadow-sm focus:border-brand-600 focus:ring-0 focus:outline-none">
                                </label>
                            </form>
                        @endif
                        <div class="ms-auto">
                            <x-dropdown align="right" width="w-56" contentClasses="bg-white py-1">
                                <x-slot name="trigger">
                                    <button type="button" class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white py-1.5 ps-3 pe-1.5 text-sm shadow-sm">
                                        <span class="hidden text-end sm:block">
                                            <span class="block max-w-40 truncate font-semibold leading-tight text-slate-900">{{ auth()->user()->name }}</span>
                                            <span class="block max-w-40 truncate text-xs text-slate-500">{{ auth()->user()->role === \App\Enums\Role::Staff ? auth()->user()->accessAreas() : auth()->user()->role->label() }}</span>
                                        </span>
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-800">
                                            {{ collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                                        </span>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->role === \App\Enums\Role::Staff ? auth()->user()->accessAreas() : auth()->user()->role->label() }}</p>
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
                    </div>
                </header>

                <main class="px-4 pb-8 sm:px-6 lg:px-8">
                    @isset($header)
                        <div class="mb-6">
                            {{ $header }}
                        </div>
                    @endisset
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
