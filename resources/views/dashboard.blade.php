<x-app-layout>
    @php
        $maxQueue = max(1, collect($queues)->max('count'));
        $quickActions = collect([
            ['label' => 'Register patient', 'hint' => 'Front desk intake', 'route' => 'patients.create', 'permission' => 'patients.manage', 'icon' => 'bx-user-plus', 'tone' => 'bg-brand-700 text-white'],
            ['label' => 'Appointments', 'hint' => 'Book and check in', 'route' => 'appointments.index', 'permission' => 'appointments.manage', 'icon' => 'bx-calendar', 'tone' => 'bg-sky-600 text-white'],
            ['label' => 'Lab worklist', 'hint' => 'Samples and results', 'route' => 'lab.index', 'permission' => 'lab.view', 'icon' => 'bx-test-tube', 'tone' => 'bg-red-600 text-white'],
            ['label' => 'Ward board', 'hint' => 'Beds and admissions', 'route' => 'wards.index', 'permission' => 'wards.view', 'icon' => 'bx-bed', 'tone' => 'bg-slate-900 text-white'],
            ['label' => 'Queues', 'hint' => 'Call the next patient', 'route' => 'queues.index', 'permission' => 'queue.view', 'icon' => 'bx-list-ol', 'tone' => 'bg-amber-500 text-white'],
            ['label' => 'Pharmacy', 'hint' => 'Dispense orders', 'route' => 'pharmacy.index', 'permission' => 'pharmacy.view', 'icon' => 'bx-capsule', 'tone' => 'bg-brand-600 text-white'],
        ])->filter(fn (array $action): bool => auth()->user()->permits($action['permission']))->take(4);
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-page-title>Facility overview</x-page-title>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-brand-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        Live
                    </span>
                </div>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="bx-buildings" class="text-base text-slate-400" />
                        {{ $currentFacility->name ?? 'Platform' }}{{ $currentFacility?->district ? ' · '.$currentFacility->district : '' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="bx-time-five" class="text-base text-slate-400" />
                        Updated {{ now()->format('H:i') }}
                    </span>
                </p>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Visits today</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon name="bx-user" class="text-xl" />
                </span>
            </div>
            <p class="mt-2 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($visitsToday) }}</p>
            <p class="mt-auto pt-4 text-sm text-slate-500">Opened since midnight</p>
        </div>

        <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Waiting</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $waiting > 0 ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}">
                    <x-icon name="bx-plus-medical" class="text-xl" />
                </span>
            </div>
            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($waiting) }}</span>
                <span class="text-sm text-slate-400">in queues</span>
            </p>
            <p class="mt-auto pt-4 text-sm text-slate-500">Across {{ count($queues) }} service points</p>
        </div>

        <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Bed occupancy</p>
                <span class="rounded-lg px-2 py-1 text-[11px] font-semibold {{ $occupancy >= 90 ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-800' }}">{{ $freeBeds }} free</span>
            </div>
            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold tracking-tight text-slate-900">{{ $occupancy }}%</span>
                <span class="text-sm text-slate-400">{{ $occupiedBeds }} / {{ $beds }} beds</span>
            </p>
            <div class="mt-auto pt-4">
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $occupancy >= 90 ? 'bg-red-600' : 'bg-brand-600' }}" style="width: {{ min($occupancy, 100) }}%"></div>
                </div>
            </div>
        </div>

        @if (auth()->user()->permits('cashier.manage') || auth()->user()->permits('reports.view'))
            <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Collected today</p>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <x-icon name="bx-wallet" class="text-xl" />
                    </span>
                </div>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ \App\Support\Money::format($revenueToday) }}</p>
                <p class="mt-auto pt-4 text-sm text-slate-500">Payments received</p>
            </div>
        @else
            <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Admitted</p>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <x-icon name="bx-bed" class="text-xl" />
                    </span>
                </div>
                <p class="mt-2 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($admitted) }}</p>
                <p class="mt-auto pt-4 text-sm text-slate-500">Inpatients on the wards</p>
            </div>
        @endif
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="overflow-hidden rounded-2xl bg-white shadow-card">
            <div class="flex items-center justify-between gap-3 p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <x-icon name="bx-list-ul" class="text-xl" />
                    </span>
                    <div>
                        <h2 class="font-bold text-slate-900">Recent visits</h2>
                        <p class="text-sm text-slate-500">Latest records opened at this facility.</p>
                    </div>
                </div>
                @if (auth()->user()->permits('patients.view'))
                    <a href="{{ route('patients.index') }}" class="hidden items-center gap-1 rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 sm:inline-flex">
                        All patients
                        <x-icon name="bx-right-arrow-alt" class="text-lg" />
                    </a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 text-left text-[11px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Patient</th>
                            <th class="px-5 py-3 font-semibold">Number</th>
                            <th class="px-5 py-3 font-semibold">Opened</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentVisits as $visit)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-800">
                                            {{ collect(preg_split('/\s+/', trim($visit->patient->fullName())))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                                        </span>
                                        <span>
                                            <span class="block font-semibold text-slate-900">{{ $visit->patient->fullName() }}</span>
                                            <span class="block text-xs text-slate-400">{{ $visit->visit_type->label() }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $visit->visit_number }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    <span class="block">{{ $visit->opened_at->format('d M Y') }}</span>
                                    <span class="block text-xs text-slate-400">{{ $visit->opened_at->format('H:i') }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand-800">{{ $visit->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-end">
                                    @if (auth()->user()->permits('patients.view'))
                                        <a href="{{ route('visits.show', $visit) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-brand-50 hover:text-brand-700" aria-label="Open {{ $visit->visit_number }}">
                                            <x-icon name="bx-chevron-right" class="text-xl" />
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No visits yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-6">
            @if ($quickActions->isNotEmpty())
                <section class="rounded-2xl bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-slate-900">Quick actions</h2>
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Fast lane</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach ($quickActions as $action)
                            <a href="{{ route($action['route']) }}" class="group rounded-2xl bg-slate-50 p-4 transition hover:bg-white hover:shadow-card-hover">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $action['tone'] }}">
                                    <x-icon :name="$action['icon']" class="text-lg" />
                                </span>
                                <span class="mt-3 block text-sm font-semibold text-slate-900">{{ $action['label'] }}</span>
                                <span class="block text-xs text-slate-400">{{ $action['hint'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900">Queues</h2>
                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $waiting }} waiting</span>
                </div>
                <ul class="mt-4 space-y-4">
                    @foreach ($queues as $queue)
                        <li class="text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-700">{{ $queue['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ $queue['count'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-600" style="width: {{ $queue['count'] > 0 ? max(4, (int) round($queue['count'] / $maxQueue * 100)) : 0 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900">Today's schedule</h2>
                        <p class="text-sm text-slate-500">{{ now()->format('l, d M') }}</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                        <x-icon name="bx-calendar" class="text-xl" />
                    </span>
                </div>
                <ul class="mt-4 space-y-2">
                    @forelse ($appointments as $appointment)
                        <li class="flex gap-3 rounded-xl bg-slate-50 px-3 py-3">
                            <span class="shrink-0 rounded-lg bg-white px-2 py-1 text-xs font-bold text-brand-700 shadow-card">{{ $appointment->scheduled_at->format('H:i') }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $appointment->patient->fullName() }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $appointment->department->name }}{{ $appointment->reason ? ' · '.$appointment->reason : '' }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-slate-50 px-3 py-4 text-sm text-slate-500">No appointments booked for today.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900">Reorder</h2>
                    @if ($lowStock->isNotEmpty())
                        <span class="rounded-lg bg-red-50 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-red-700">Low stock</span>
                    @endif
                </div>
                <ul class="mt-4 space-y-2">
                    @forelse ($lowStock as $item)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 text-sm">
                            <span class="font-medium text-slate-700">{{ $item->name }}</span>
                            <span class="shrink-0 text-xs font-semibold text-red-700">{{ (int) $item->main_quantity }} / {{ $item->reorder_level }}</span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-slate-50 px-3 py-4 text-sm text-slate-500">Nothing is under its reorder level.</li>
                    @endforelse
                </ul>
                <p class="mt-4 flex items-center gap-2 text-sm text-slate-500">
                    <x-icon name="bx-bed" class="text-base text-slate-400" />
                    {{ $admitted }} patient{{ $admitted === 1 ? '' : 's' }} currently admitted.
                </p>
            </section>
        </div>
    </div>
</x-app-layout>
