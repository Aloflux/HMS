<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-page-title>Facility overview</x-page-title>
                <p class="mt-1 text-sm text-slate-500">{{ $currentFacility->name ?? 'Platform' }}{{ $currentFacility?->district ? ' · '.$currentFacility->district : '' }}</p>
            </div>
            @if (auth()->user()->permits('patients.manage'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white">
                    <x-icon name="bx-plus" class="text-lg" />
                    New registration
                </a>
            @endif
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <x-icon name="bx-user" class="text-xl" />
            </span>
            <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Visits today</p>
            <p class="mt-1 text-3xl font-semibold tracking-tight">{{ number_format($visitsToday) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <x-icon name="bx-plus-medical" class="text-xl" />
            </span>
            <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Waiting</p>
            <p class="mt-1 text-3xl font-semibold tracking-tight">{{ number_format($waiting) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                    <x-icon name="bx-bed" class="text-xl" />
                </span>
                <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-medium text-brand-800">{{ $freeBeds }} free</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Bed occupancy</p>
            <p class="mt-1 text-3xl font-semibold tracking-tight">{{ $occupancy }}%</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <x-icon name="bx-wallet" class="text-xl" />
            </span>
            <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Collected today</p>
            <p class="mt-1 text-3xl font-semibold tracking-tight">{{ \App\Support\Money::format($revenueToday) }}</p>
        </div>
    </div>

    <div class="mt-4 grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-lg bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 px-5 pt-5">
                <div>
                    <h2 class="font-semibold">Recent visits</h2>
                    <p class="text-sm text-slate-500">Latest records opened at this facility.</p>
                </div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">Patient</th>
                            <th class="px-5 py-3 font-medium">Number</th>
                            <th class="px-5 py-3 font-medium">Opened</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentVisits as $visit)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-800">
                                            {{ collect(preg_split('/\s+/', trim($visit->patient->fullName())))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                                        </span>
                                        <span>
                                            <span class="block font-medium text-slate-900">{{ $visit->patient->fullName() }}</span>
                                            <span class="block text-xs uppercase tracking-wide text-slate-400">{{ $visit->visit_type->label() }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $visit->visit_number }}</td>
                                <td class="px-5 py-3 text-slate-600">
                                    <span class="block">{{ $visit->opened_at->format('d M Y') }}</span>
                                    <span class="block text-xs text-slate-400">{{ $visit->opened_at->format('H:i') }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-medium uppercase tracking-wide text-brand-800">{{ $visit->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 text-end">
                                    @if (auth()->user()->permits('patients.view'))
                                        <a href="{{ route('visits.show', $visit) }}" class="text-slate-400 hover:text-brand-700" aria-label="Open {{ $visit->visit_number }}">
                                            <x-icon name="bx-chevron-right" class="text-xl" />
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-slate-500">No visits yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-4">
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Today's schedule</h2>
                <p class="text-sm text-slate-500">{{ now()->format('l, d M') }}</p>
                <ul class="mt-4 space-y-3">
                    @forelse ($appointments as $appointment)
                        <li class="rounded-lg bg-slate-50 px-3 py-3">
                            <p class="text-xs font-medium text-slate-400">{{ $appointment->scheduled_at->format('H:i') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $appointment->patient->fullName() }}</p>
                            <p class="text-sm text-slate-500">{{ $appointment->department->name }}{{ $appointment->reason ? ' · '.$appointment->reason : '' }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No appointments booked for today.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Queues</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($queues as $queue)
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <span class="text-slate-700">{{ $queue['label'] }}</span>
                            <span class="font-medium text-slate-900">{{ $queue['count'] }} waiting</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Reorder</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @forelse ($lowStock as $item)
                        <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <span class="text-slate-700">{{ $item->name }}</span>
                            <span class="shrink-0 text-slate-500">{{ (int) $item->main_quantity }} / {{ $item->reorder_level }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">Nothing is under its reorder level.</li>
                    @endforelse
                </ul>
                <p class="mt-4 text-sm text-slate-500">{{ $admitted }} patient{{ $admitted === 1 ? '' : 's' }} currently admitted.</p>
            </section>
        </div>
    </div>
</x-app-layout>
