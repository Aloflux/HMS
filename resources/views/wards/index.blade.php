<x-app-layout>
    <x-slot name="header"><x-page-title>Wards</x-page-title></x-slot>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($wards as $ward)
            @php($free = $ward->beds->where('status', 'available')->count())
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">{{ $ward->name }}</h2>
                    <span class="text-sm text-slate-500">{{ $free }} of {{ $ward->beds->count() }} free</span>
                </div>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($ward->beds as $bed)
                        <li class="flex items-center justify-between gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <span class="font-medium">{{ $bed->code }}</span>
                            <span class="flex items-center gap-2">
                                <x-status :value="$bed->status === 'available' ? 'free' : $bed->status" />
                                @if ($bed->status === 'cleaning' && auth()->user()->permits('wards.manage'))
                                    <form method="POST" action="{{ route('wards.beds.clean', $bed) }}">@csrf<button class="text-xs font-semibold text-brand-700">Mark clean</button></form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Inpatients <span class="font-normal text-slate-500">· {{ $admissions->count() }}</span></h2>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @forelse ($admissions as $admission)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <a class="font-medium text-brand-700" href="{{ route('wards.show', $admission) }}">{{ $admission->patient->fullName() }}</a>
                    <span class="text-slate-500">{{ $admission->bed->ward->name }} {{ $admission->bed->code }} · {{ $admission->diagnosis_summary }} · day {{ (int) $admission->admitted_at->diffInDays(now()) + 1 }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No patients are admitted.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
