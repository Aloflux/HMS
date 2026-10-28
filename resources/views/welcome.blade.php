<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Tunzuri') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-brand-800">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white">
                        <x-icon name="bx-plus-medical" class="text-xl" />
                    </span>
                    <span class="text-lg font-semibold">Tunzuri</span>
                </a>
                <nav class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="whitespace-nowrap rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Sign in</a>
                    <a href="{{ route('register') }}" class="whitespace-nowrap rounded-xl bg-brand-700 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                        <span class="sm:hidden">Register</span>
                        <span class="hidden sm:inline">Register your facility</span>
                    </a>
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto grid max-w-6xl items-center gap-10 px-6 py-12 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:py-20">
                <div class="flex flex-col gap-6">
                    <p class="text-sm font-semibold text-brand-700">For clinics and hospitals</p>
                    <h1 class="max-w-xl text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">One record, from the queue to the receipt.</h1>
                    <p class="max-w-xl text-base leading-7 text-slate-600">Tunzuri keeps registration, the queue, consultation, the laboratory, pharmacy, and the cashier on the same visit. What was done is what gets billed.</p>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
                            <x-icon name="bx-buildings" class="text-lg" />
                            Register your facility
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 hover:border-brand-600">
                            <x-icon name="bx-log-in" class="text-lg" />
                            Sign in
                        </a>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">A visit, on one record</p>
                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">Outpatient</span>
                    </div>
                    <div class="mt-4 flex items-center gap-3">
                        @php
                            $samplePatient = 'Emma Clarke';
                            $sampleInitials = collect(preg_split('/\s+/', trim($samplePatient)))
                                ->filter()
                                ->take(2)
                                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-700 text-sm font-semibold text-white">{{ $sampleInitials }}</span>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $samplePatient }}</p>
                            <p class="text-sm text-slate-500">Fever and headache · OPD</p>
                        </div>
                    </div>
                    <ol class="mt-5 flex flex-col gap-3">
                        @foreach ([
                            ['bx-id-card', 'Registered', 'Front office opened the visit'],
                            ['bx-pulse', 'Triaged', 'Urgent, sent to consultation'],
                            ['bx-test-tube', 'Laboratory', 'Malaria test ordered'],
                            ['bx-capsule', 'Pharmacy', 'Waiting to dispense'],
                        ] as [$icon, $title, $detail])
                            <li class="flex items-start gap-3">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                    <x-icon :name="$icon" class="text-lg" />
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ $title }}</span>
                                    <span class="block text-sm text-slate-500">{{ $detail }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            <section class="mx-auto flex max-w-6xl flex-col gap-8 px-6 py-14">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-semibold tracking-tight">Two ways a visit ends</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Outpatients pay before they leave. Admitted patients carry a running bill through the ward and settle it at discharge. Both paths close at the pharmacy, so the medicine and the charge leave together.</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ([
                        ['Outpatient', 'Settled before they leave', [
                            ['bx-id-card', 'Register', 'A new patient or a returning one, on the same record.'],
                            ['bx-pulse', 'Triage', 'Vitals, a priority, and a place in the queue.'],
                            ['bx-plus-medical', 'Consult', 'Notes, a diagnosis, and orders for the laboratory or pharmacy.'],
                            ['bx-wallet', 'Cashier', 'Cash, mobile money, or an insurer, against the charges already posted.'],
                            ['bx-capsule', 'Pharmacy', 'Dispense from the shelf and take that stock off the batch.'],
                        ]],
                        ['Admitted', 'A running bill until discharge', [
                            ['bx-plus-medical', 'Decision to admit', 'The same consultation, with a bed requested instead of a queue ticket home.'],
                            ['bx-bed', 'Ward', 'Rounds, nursing notes, and medicines given from the ward store.'],
                            ['bx-file', 'Discharge', 'A summary, then the final bill for everything still open.'],
                            ['bx-capsule', 'Pharmacy', 'What goes home is dispensed from the pharmacy shelf.'],
                        ]],
                    ] as [$path, $summary, $steps])
                        <article class="rounded-lg bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ $path }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $summary }}</p>
                                </div>
                            </div>
                            <ol class="mt-5 flex flex-col">
                                @foreach ($steps as [$icon, $title, $detail])
                                    <li class="relative flex gap-3 {{ $loop->last ? '' : 'pb-5' }}">
                                        @unless ($loop->last)
                                            <span class="absolute start-4 top-8 bottom-0 w-px bg-brand-100"></span>
                                        @endunless
                                        <span class="relative z-10 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                            <x-icon :name="$icon" class="text-lg" />
                                        </span>
                                        <span>
                                            <span class="block text-sm font-semibold text-slate-900">{{ $title }}</span>
                                            <span class="mt-0.5 block text-sm leading-6 text-slate-500">{{ $detail }}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </article>
                    @endforeach
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['bx-folder', 'One record', 'Orders, results, and charges stay on the visit that created them.'],
                        ['bx-receipt', 'Billed as it happens', 'Registration, a test, and a dispensed medicine each post their own charge.'],
                        ['bx-spreadsheet', 'Counted from the day', 'Daily revenue and the monthly Ministry returns come from that same work.'],
                    ] as [$icon, $title, $copy])
                        <div class="flex gap-3 rounded-lg bg-white p-4 shadow-sm">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <x-icon :name="$icon" class="text-xl" />
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $title }}</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-500">{{ $copy }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        </main>

        <footer class="bg-brand-900 text-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-lg font-semibold">Tunzuri</p>
                    <p class="mt-1 text-sm text-brand-100">Open a facility, then give each person only the areas they work in.</p>
                </div>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-900 hover:bg-brand-50">
                    Register your facility
                    <x-icon name="bx-right-arrow-alt" class="text-lg" />
                </a>
            </div>
        </footer>
    </body>
</html>
