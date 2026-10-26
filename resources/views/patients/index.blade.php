<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-page-title>Patient directory</x-page-title>
                <p class="mt-1 text-sm text-slate-500">{{ $currentFacility->name ?? 'Platform' }}</p>
            </div>
            @if (auth()->user()->permits('patients.manage'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 rounded-md bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-brand hover:bg-brand-800">
                    <x-icon name="bx-plus" class="text-lg" />
                    Register patient
                </a>
            @endif
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-user" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total patients</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($totalPatients) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-bed" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Inpatients</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($inpatients) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-plus-medical" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Opened today</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($openedToday) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-shield-quarter" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Insurance</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ $insuranceRate }}%</p>
        </div>
    </div>

    <section class="mt-4 rounded-lg bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-5">
            <div class="flex flex-wrap items-center gap-1">
                <h2 class="me-3 font-bold text-slate-900">All patients</h2>
                @foreach (['all' => 'All', 'inpatient' => 'Inpatient', 'outpatient' => 'Outpatient'] as $value => $label)
                    <a href="{{ route('patients.index', array_filter(['q' => $search, 'care' => $value === 'all' ? null : $value, 'sort' => $sort === 'latest' ? null : $sort])) }}" class="rounded-md px-3 py-1.5 text-sm {{ $care === $value ? 'bg-brand-50 font-semibold text-brand-800' : 'text-slate-500 hover:bg-slate-50' }}">{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" class="flex items-center gap-2 text-sm text-slate-500">
                @if ($search !== '')
                    <input type="hidden" name="q" value="{{ $search }}">
                @endif
                @if ($care !== 'all')
                    <input type="hidden" name="care" value="{{ $care }}">
                @endif
                <label for="sort">Sort by</label>
                <select id="sort" name="sort" class="rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm shadow-none focus:outline-none" @change="$el.form.submit()">
                    <option value="latest" @selected($sort === 'latest')>Latest registered</option>
                    <option value="name" @selected($sort === 'name')>Name</option>
                </select>
            </form>
        </div>

        <form method="GET" class="mt-4 px-5 md:hidden">
            <input type="search" name="q" value="{{ $search }}" placeholder="Name, phone, patient number or national ID" class="w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 shadow-none focus:outline-none">
            @if ($care !== 'all')
                <input type="hidden" name="care" value="{{ $care }}">
            @endif
        </form>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 font-medium">Patient</th>
                        <th class="px-5 py-3 font-medium">Number</th>
                        <th class="px-5 py-3 font-medium">Sex / age</th>
                        <th class="px-5 py-3 font-medium">Last visit</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($patients as $patient)
                        @php
                            $visit = $patient->latestVisit;
                            $statusClass = match (true) {
                                $visit?->priority === \App\Enums\Priority::Emergency => 'bg-red-50 text-red-700',
                                $visit === null || in_array($visit->status, [\App\Enums\VisitStatus::Discharged, \App\Enums\VisitStatus::Closed], true) => 'bg-slate-100 text-slate-600',
                                default => 'bg-brand-50 text-brand-800',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('patients.show', $patient) }}" class="flex items-center gap-3">
                                    @if ($patient->photo_path)
                                        <img src="{{ route('patients.photo', $patient) }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                    @else
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-800">{{ $patient->initials() }}</span>
                                    @endif
                                    <span>
                                        <span class="block font-medium text-slate-900">{{ $patient->fullName() }}</span>
                                        <span class="block text-xs text-slate-400">{{ $patient->phone }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $patient->patient_number }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                <span class="block">{{ $patient->sex->label() }}, {{ $patient->ageInYears() }} yrs</span>
                                @if ($patient->blood_group)
                                    <span class="block text-xs uppercase tracking-wide text-slate-400">{{ $patient->blood_group }} blood group</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @if ($visit)
                                    <span class="block">{{ $visit->opened_at->format('d M Y') }}</span>
                                    <span class="block text-xs text-slate-400">{{ $visit->clinician?->name ?? $visit->visit_type->label() }}</span>
                                @else
                                    <span class="text-slate-400">No visit</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium uppercase tracking-wide {{ $statusClass }}">{{ $visit ? $visit->status->label() : 'Registered' }}</span>
                            </td>
                            <td class="px-5 py-3 text-end">
                                <span class="inline-flex items-center gap-2">
                                    <a href="{{ route('patients.show', $patient) }}" class="text-slate-400 hover:text-brand-700" aria-label="Open {{ $patient->fullName() }}">
                                        <x-icon name="bx-show" class="text-xl" />
                                    </a>
                                    @if (auth()->user()->permits('patients.manage'))
                                        <a href="{{ route('patients.edit', $patient) }}" class="text-slate-400 hover:text-brand-700" aria-label="Edit {{ $patient->fullName() }}">
                                            <x-icon name="bx-edit" class="text-xl" />
                                        </a>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-slate-500">No patients match that search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $patients->links() }}</div>
</x-app-layout>
