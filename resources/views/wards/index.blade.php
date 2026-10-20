<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-bed">Wards</x-page-title></x-slot>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($wards as $ward)
            <section class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="font-semibold">{{ $ward->name }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($ward->beds as $bed)
                        <span class="rounded-xl px-2 py-1 text-xs {{ $bed->status === 'available' ? 'bg-brand-50 text-brand-800' : 'bg-slate-100' }}">{{ $bed->code }} {{ $bed->status }}</span>
                        @if ($bed->status === 'cleaning' && auth()->user()->permits('wards.manage'))
                            <form method="POST" action="{{ route('wards.beds.clean', $bed) }}">@csrf<button class="text-xs text-brand-700">Mark clean</button></form>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Inpatients</h2>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @foreach ($admissions as $admission)
                <li class="py-3"><a class="text-brand-700" href="{{ route('wards.show', $admission) }}">{{ $admission->patient->fullName() }}</a> · {{ $admission->bed->code }}</li>
            @endforeach
        </ul>
    </section>
</x-app-layout>
