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
    <form method="GET" class="mb-6 flex flex-wrap gap-2">
        <input type="date" name="start" value="{{ $start }}" class="rounded-md border-slate-300 text-sm">
        <input type="date" name="end" value="{{ $end }}" class="rounded-md border-slate-300 text-sm">
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm text-white">Update</button>
    </form>
    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Cash collected</p><p class="mt-2 text-2xl font-semibold">{{ \App\Support\Money::format($revenue) }}</p></section>
        <section class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Dispenses without a charge</p><p class="mt-2 text-2xl font-semibold">{{ $unbilledDispenses }}</p></section>
        <section class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Ward issues in 7 days</p><p class="mt-2 text-2xl font-semibold">{{ $wardIssuesThisWeek }}</p></section>
    </div>
    <section class="mt-6 overflow-x-auto rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">HMIS 105 outpatient attendance</h2>
        <table class="mt-3 min-w-full text-sm">
            <thead><tr class="text-left text-slate-500"><th class="py-2">Sex</th><th>Age</th><th>New</th><th>Reattendance</th></tr></thead>
            <tbody>
                @foreach ($outpatient['attendance'] as $row)
                    <tr><td class="py-1">{{ $row['sex'] }}</td><td>{{ $row['band'] }}</td><td>{{ $row['new'] }}</td><td>{{ $row['reattendance'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <h2 class="mt-6 font-semibold">Diagnoses</h2>
        <ul class="mt-2 text-sm">
            @foreach ($outpatient['diagnoses'] as $diagnosis)
                <li>{{ $diagnosis->icd10_code }} {{ $diagnosis->title }} · {{ $diagnosis->total }}</li>
            @endforeach
        </ul>
    </section>
    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm text-sm">
        <h2 class="font-semibold">HMIS 108 inpatient</h2>
        <p class="mt-2">Admissions {{ $inpatient['admissions'] }} · Discharges {{ $inpatient['discharges'] }} · Deaths {{ $inpatient['deaths'] }}</p>
        <p>Bed days {{ $inpatient['bed_days'] }} · Occupancy {{ $inpatient['occupancy'] }}% of {{ $inpatient['beds'] }} beds</p>
    </section>
</x-app-layout>
