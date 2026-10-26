<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-title icon="bx-bar-chart-alt-2">Reports</x-page-title>
            <a class="inline-flex items-center gap-1 text-sm text-brand-700" href="{{ route('reports.export', ['start' => $start, 'end' => $end]) }}">
                <x-icon name="bx-export" class="text-lg" />
                Export HMIS CSV
            </a>
        </div>
    </x-slot>
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <x-input-label for="report-start" value="From" />
            <x-text-input id="report-start" type="date" name="start" :value="$start" class="mt-1 block" />
        </div>
        <div>
            <x-input-label for="report-end" value="To" />
            <x-text-input id="report-end" type="date" name="end" :value="$end" class="mt-1 block" />
        </div>
        <x-primary-button>Update</x-primary-button>
    </form>
    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-2xl bg-white p-5 shadow-card"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Cash collected</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ \App\Support\Money::format($revenue) }}</p></section>
        <section class="rounded-2xl bg-white p-5 shadow-card"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Dispenses without a charge</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $unbilledDispenses }}</p></section>
        <section class="rounded-2xl bg-white p-5 shadow-card"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Ward issues in 7 days</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $wardIssuesThisWeek }}</p></section>
    </div>
    <section class="mt-6 overflow-x-auto rounded-2xl bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">HMIS 105 outpatient attendance</h2>
        <table class="mt-3 min-w-full text-sm">
            <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-50/80"><th class="py-2 font-medium">Sex</th><th class="font-medium">Age</th><th class="font-medium">New</th><th class="font-medium">Reattendance</th></tr></thead>
            <tbody>
                @foreach ($outpatient['attendance'] as $row)
                    <tr><td class="py-1">{{ $row['sex'] }}</td><td>{{ $row['band'] }}</td><td>{{ $row['new'] }}</td><td>{{ $row['reattendance'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <h2 class="mt-6 font-bold text-slate-900">Diagnoses</h2>
        <ul class="mt-2 text-sm">
            @foreach ($outpatient['diagnoses'] as $diagnosis)
                <li>{{ $diagnosis->icd10_code }} {{ $diagnosis->title }} · {{ $diagnosis->total }}</li>
            @endforeach
        </ul>
    </section>
    <section class="mt-6 rounded-2xl bg-white p-5 shadow-card text-sm">
        <h2 class="font-bold text-slate-900">HMIS 108 inpatient</h2>
        <p class="mt-2">Admissions {{ $inpatient['admissions'] }} · Discharges {{ $inpatient['discharges'] }} · Deaths {{ $inpatient['deaths'] }}</p>
        <p>Bed days {{ $inpatient['bed_days'] }} · Occupancy {{ $inpatient['occupancy'] }}% of {{ $inpatient['beds'] }} beds</p>
    </section>
</x-app-layout>
