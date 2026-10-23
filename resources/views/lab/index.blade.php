<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Laboratory</x-page-title>
            <p class="mt-1 text-sm text-slate-500">Oldest request first. Verifying releases the result to the clinician and texts the patient.</p>
        </div>
    </x-slot>

    <h2 class="mb-3 font-semibold">Waiting for a result <span class="font-normal text-slate-500">· {{ $items->count() }}</span></h2>
    <div class="grid gap-3">
        @forelse ($items as $item)
            <section class="rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $item->test->name }}{{ $item->test->specimen ? ' · '.$item->test->specimen : '' }}</p>
                        <p class="text-sm text-slate-500">
                            <a href="{{ route('visits.show', $item->request->visit) }}" class="text-brand-700">{{ $item->request->visit->patient->fullName() }}</a>
                            · {{ $item->request->visit->visit_number }} · ordered {{ $item->created_at->diffForHumans() }}
                        </p>
                        @if ($item->request->clinical_notes)
                            <p class="mt-1 text-sm text-slate-600">{{ $item->request->clinical_notes }}</p>
                        @endif
                    </div>
                    <x-status :value="$item->status" />
                </div>
                @if (auth()->user()->permits('lab.manage'))
                    <form method="POST" action="{{ route('lab.verify', $item) }}" class="mt-3 grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-4 sm:items-end">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label :for="'result-'.$item->id" :value="'Result'.($item->test->unit ? ' ('.$item->test->unit.')' : '')" />
                            <x-text-input :id="'result-'.$item->id" name="result_value" class="mt-1 block w-full" required />
                            @if ($item->test->reference_text)
                                <p class="mt-1 text-xs text-slate-500">Reference {{ $item->test->reference_text }}</p>
                            @endif
                        </div>
                        <div>
                            <x-input-label :for="'flag-'.$item->id" value="Flag" />
                            <select id="flag-{{ $item->id }}" name="result_flag" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                                <option value="normal">Normal</option>
                                <option value="abnormal">Abnormal</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="sm:pb-0.5"><x-primary-button>Verify result</x-primary-button></div>
                    </form>
                @endif
            </section>
        @empty
            <p class="rounded-lg bg-white p-5 text-sm text-slate-500 shadow-sm">No samples are waiting.</p>
        @endforelse
    </div>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Recently verified</h2>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @forelse ($recent as $item)
                <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                    <span>
                        <span class="font-medium">{{ $item->test->name }}</span>
                        · <span class="{{ in_array($item->result_flag, ['abnormal', 'critical'], true) ? 'font-semibold text-red-700' : '' }}">{{ $item->result_value }} {{ $item->test->unit }}</span>
                        <span class="block text-xs text-slate-500">
                            <a href="{{ route('visits.show', $item->request->visit) }}" class="text-brand-700">{{ $item->request->visit->patient->fullName() }}</a>
                            · {{ $item->verifier?->name }} · {{ $item->verified_at?->format('d M H:i') }}
                        </span>
                    </span>
                    @if (in_array($item->result_flag, ['abnormal', 'critical'], true))
                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">{{ ucfirst($item->result_flag) }}</span>
                    @endif
                </li>
            @empty
                <li class="py-2 text-slate-500">Nothing verified yet.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
