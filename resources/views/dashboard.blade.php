<x-app-layout>
    <x-slot name="header">
        <x-page-title icon="bx-pulse">Today</x-page-title>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="bx-clipboard" class="text-lg text-brand-700" /> Visits opened</p>
            <p class="mt-2 text-3xl font-semibold">{{ $visitsToday }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="bx-wallet" class="text-lg text-brand-700" /> Cash collected</p>
            <p class="mt-2 text-3xl font-semibold">{{ \App\Support\Money::format($revenueToday) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="bx-time-five" class="text-lg text-brand-700" /> Waiting in queues</p>
            <p class="mt-2 text-3xl font-semibold">{{ $waiting }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="bx-bed" class="text-lg text-brand-700" /> Beds occupied</p>
            <p class="mt-2 text-3xl font-semibold">{{ $occupiedBeds }} / {{ $beds }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Recent visits</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($recentVisits as $visit)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div>
                            @if (auth()->user()->permits('patients.view'))
                                <a href="{{ route('visits.show', $visit) }}" class="font-medium text-brand-700">{{ $visit->visit_number }}</a>
                            @else
                                <span class="font-medium">{{ $visit->visit_number }}</span>
                            @endif
                            <p class="text-slate-500">{{ $visit->patient->fullName() }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ $visit->status->label() }}</span>
                    </li>
                @empty
                    <li class="py-6 text-sm text-slate-500">No visits yet today.</li>
                @endforelse
            </ul>
        </section>
        <section class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Main store at or below reorder</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($lowStock as $item)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <span>{{ $item->name }}</span>
                        <span class="text-slate-500">{{ (int) $item->main_quantity }} / {{ $item->reorder_level }}</span>
                    </li>
                @empty
                    <li class="py-6 text-sm text-slate-500">No item is under its reorder level.</li>
                @endforelse
            </ul>
            <p class="mt-4 text-sm text-slate-500">{{ $admitted }} patient{{ $admitted === 1 ? '' : 's' }} currently admitted.</p>
        </section>
    </div>
</x-app-layout>
