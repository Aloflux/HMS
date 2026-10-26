<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="idle-timeout" content="{{ config('hms.idle_timeout_minutes') }}">

        @php($pageTitle = \App\Support\Navigation::titleFor(request()->route()?->getName()))
        <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ config('app.name', 'Tunzuri') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div x-data="{ sidebar: false }" class="min-h-screen">
            <div x-show="sidebar" x-cloak class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden" @click="sidebar = false"></div>
            @include('layouts.navigation')

            <div class="lg:ps-64">
                <header class="no-print sticky top-0 z-20 bg-white/90 shadow-card backdrop-blur-md">
                    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-700 hover:bg-slate-200 lg:hidden" @click="sidebar = !sidebar">
                            <x-icon name="bx-menu" class="text-xl" />
                            <span class="sr-only">Menu</span>
                        </button>
                        @if (auth()->user()->permits('patients.view'))
                            <form method="GET" action="{{ route('patients.index') }}" class="min-w-0 flex-1 md:max-w-xs">
                                <label class="relative block">
                                    <span class="sr-only">Search patients</span>
                                    <x-icon name="bx-search" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" />
                                    <input type="search" name="q" value="{{ request()->routeIs('patients.index') ? request('q') : '' }}" placeholder="Search patients" class="h-10 w-full rounded-md border-transparent bg-slate-100 ps-10 pe-3 text-sm placeholder:text-slate-400 focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none">
                                </label>
                            </form>
                        @endif

                        <div class="hidden items-center gap-2 xl:flex">
                            <span class="inline-flex h-10 items-center gap-2 rounded-md bg-slate-100 px-3 text-xs">
                                <x-icon name="bx-calendar" class="text-base text-slate-400" />
                                <span class="font-semibold text-slate-700">{{ now()->format('D, d M') }}</span>
                            </span>
                            <span class="inline-flex h-10 items-center gap-2 rounded-md bg-brand-50 px-3 text-xs font-semibold text-brand-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                On duty
                            </span>
                        </div>

                        <div class="ms-auto flex items-center gap-3">
                            @if (auth()->user()->permits('patients.manage'))
                                <a href="{{ route('patients.create') }}" class="hidden h-10 items-center gap-2 rounded-md bg-brand-700 px-4 text-sm font-semibold text-white shadow-brand transition hover:bg-brand-800 sm:inline-flex">
                                    <x-icon name="bx-plus" class="text-lg" />
                                    New registration
                                </a>
                            @endif
                            <span class="hidden h-8 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                            <x-dropdown align="right" width="w-56" contentClasses="bg-white py-1">
                                <x-slot name="trigger">
                                    <button type="button" class="inline-flex h-10 items-center gap-3 rounded-md ps-1 pe-1 text-sm transition hover:bg-slate-100 sm:ps-3">
                                        <span class="hidden text-end sm:block">
                                            <span class="block max-w-40 truncate text-sm font-semibold leading-tight text-slate-900">{{ auth()->user()->name }}</span>
                                            <span class="block max-w-40 truncate text-xs leading-tight text-slate-500">{{ auth()->user()->role === \App\Enums\Role::Staff ? auth()->user()->accessAreas() : auth()->user()->role->label() }}</span>
                                        </span>
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                                            {{ collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                                        </span>
                                        <x-icon name="bx-chevron-down" class="hidden text-lg text-slate-400 sm:block" />
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="bg-slate-50 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->role === \App\Enums\Role::Staff ? auth()->user()->accessAreas() : auth()->user()->role->label() }}</p>
                                    </div>
                                    <x-dropdown-link :href="route('profile.edit')">
                                        <x-icon name="bx-cog" class="text-lg text-brand-700" />
                                        Profile & settings
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

                <main class="px-4 pt-6 pb-10 sm:px-6 lg:px-8">
                    @isset($header)
                        <div class="mb-6">
                            {{ $header }}
                        </div>
                    @endisset
                    @if (session('status'))
                        <div class="mb-4 flex items-start gap-2 rounded-lg bg-brand-50 px-4 py-3 text-sm font-medium text-brand-900 shadow-card">
                            <x-icon name="bx-check-circle" class="mt-0.5 text-lg" />
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 flex items-start gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-card">
                            <x-icon name="bx-error-circle" class="mt-0.5 text-lg" />
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 flex items-start gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-card">
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
