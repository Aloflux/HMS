@php
    $canResult = auth()->user()->permits('lab.manage');
    $oldestMinutes = $stats['oldest'] ? (int) \Illuminate\Support\Carbon::parse($stats['oldest'])->diffInMinutes(now()) : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Laboratory</x-page-title>
            <p class="mt-1 text-sm text-slate-500">Emergencies first, then urgent, then oldest. Verifying releases the result to the clinician and texts the patient.</p>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-test-tube" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tests waiting</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['waiting']) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $stats['urgent'] > 0 ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-700' }}"><x-icon name="bx-error" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Urgent requests</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight {{ $stats['urgent'] > 0 ? 'text-red-700' : 'text-slate-900' }}">{{ number_format($stats['urgent']) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $oldestMinutes !== null && $oldestMinutes > 120 ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}"><x-icon name="bx-time-five" class="text-xl" /></span>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Longest wait</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">
                @if ($oldestMinutes === null)
                    —
                @elseif ($oldestMinutes < 60)
                    {{ $oldestMinutes }} min
                @else
                    {{ intdiv($oldestMinutes, 60) }} h {{ $oldestMinutes % 60 }} min
                @endif
            </p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-check-double" class="text-xl" /></span>
                @if ($stats['flaggedToday'] > 0)
                    <span class="rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700">{{ $stats['flaggedToday'] }} flagged</span>
                @endif
            </div>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Verified today</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['verifiedToday']) }}</p>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="inline-flex rounded-lg bg-white p-1 shadow-card" aria-label="Laboratory views">
            @foreach (['waiting' => 'Waiting for result', 'verified' => 'Verified results'] as $value => $label)
                <a
                    href="{{ route('lab.index', array_filter(['tab' => $value === 'waiting' ? null : $value, 'q' => $search ?: null])) }}"
                    class="rounded-md px-4 py-1.5 text-sm {{ $tab === $value ? 'bg-brand-700 font-semibold text-white' : 'text-slate-600 hover:text-slate-900' }}"
                    @if ($tab === $value) aria-current="page" @endif
                >
                    {{ $label }}
                    @if ($value === 'waiting')
                        <span class="ms-1 {{ $tab === $value ? 'text-white/80' : 'text-slate-400' }}">{{ $stats['waiting'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('lab.index') }}" class="w-full sm:w-80">
            @if ($tab === 'verified')
                <input type="hidden" name="tab" value="verified">
            @endif
            <label class="relative block">
                <span class="sr-only">Search the laboratory</span>
                <x-icon name="bx-search" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Patient, number, visit, or test" class="w-full rounded-lg border-slate-200 bg-white py-2 ps-10 pe-4 text-sm shadow-card focus:border-brand-600 focus:ring-0 focus:outline-none">
            </label>
        </form>
    </div>

    @if ($tab === 'waiting')
        <div class="mt-4 grid gap-4">
            @forelse ($requests as $labRequest)
                @php
                    $visit = $labRequest->visit;
                    $patient = $visit->patient;
                    $waited = (int) $labRequest->created_at->diffInMinutes(now());
                    $waitTone = match (true) {
                        $waited > 120 => 'text-red-700',
                        $waited > 60 => 'text-amber-700',
                        default => 'text-slate-500',
                    };
                    $priorityTone = match ($visit->priority) {
                        \App\Enums\Priority::Emergency => 'bg-red-600 text-white',
                        \App\Enums\Priority::Urgent => 'bg-amber-100 text-amber-800',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <article class="overflow-hidden rounded-lg bg-white {{ $visit->priority === \App\Enums\Priority::Emergency ? 'shadow-danger' : 'shadow-card' }}">
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-800">{{ $patient->initials() }}</span>
                            <div>
                                <a href="{{ route('visits.show', $visit) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $patient->fullName() }}</a>
                                <p class="text-sm text-slate-500">{{ $patient->patient_number }} · {{ $patient->sex->label() }}, {{ $patient->ageInYears() }} yrs · Visit {{ $visit->visit_number }}</p>
                                @if ($patient->allergies)
                                    <p class="mt-1 text-xs font-medium text-red-700">Allergies: {{ $patient->allergies }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 text-sm">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold uppercase tracking-wide {{ $priorityTone }}">{{ $visit->priority->label() }}</span>
                            <span class="{{ $waitTone }}">Waiting {{ $labRequest->created_at->diffForHumans(null, true) }}</span>
                        </div>
                    </header>

                    @if ($labRequest->clinical_notes || $labRequest->requester)
                        <p class="border-b border-slate-100 bg-slate-50 px-5 py-2 text-sm text-slate-600">
                            @if ($labRequest->requester)
                                <span class="font-medium text-slate-700">{{ $labRequest->requester->name }}</span>
                            @endif
                            {{ $labRequest->clinical_notes ? '· '.$labRequest->clinical_notes : '' }}
                        </p>
                    @endif

                    <ul class="divide-y divide-slate-100">
                        @foreach ($labRequest->items as $item)
                            <li class="px-5 py-4">
                                <form method="POST" action="{{ route('lab.verify', $item) }}" class="grid gap-3 lg:grid-cols-12 lg:items-center">
                                    @csrf
                                    <div class="lg:col-span-4">
                                        <p class="font-medium text-slate-900">{{ $item->test->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $item->test->specimen ?: 'Specimen not set' }}
                                            @if ($item->test->reference_text)
                                                · Reference {{ $item->test->reference_text }} {{ $item->test->unit }}
                                            @endif
                                        </p>
                                    </div>
                                    @if ($canResult)
                                        <div class="lg:col-span-3">
                                            <label for="result-{{ $item->id }}" class="sr-only">Result for {{ $item->test->name }}</label>
                                            <div class="flex items-center rounded-md border border-transparent bg-slate-100/70 focus-within:border-brand-600 focus-within:bg-white">
                                                <input id="result-{{ $item->id }}" name="result_value" required placeholder="Result" class="w-full min-w-0 rounded-md border-0 bg-transparent text-sm focus:ring-0 focus:outline-none">
                                                @if ($item->test->unit)
                                                    <span class="shrink-0 pe-3 text-xs text-slate-500">{{ $item->test->unit }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <fieldset class="lg:col-span-3">
                                            <legend class="sr-only">Flag</legend>
                                            <div class="inline-flex rounded-md bg-slate-100 p-0.5 text-xs font-medium">
                                                @foreach (['normal' => ['Normal', 'has-checked:bg-white has-checked:text-slate-900 has-checked:shadow-sm'], 'abnormal' => ['Abnormal', 'has-checked:bg-amber-100 has-checked:text-amber-900'], 'critical' => ['Critical', 'has-checked:bg-red-600 has-checked:text-white']] as $flag => [$label, $checkedClasses])
                                                    <label class="cursor-pointer rounded-md px-2.5 py-1.5 text-slate-600 {{ $checkedClasses }}">
                                                        <input type="radio" name="result_flag" value="{{ $flag }}" class="sr-only" @checked($flag === 'normal')>
                                                        {{ $label }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                        <div class="lg:col-span-2 lg:text-end">
                                            <x-primary-button>Verify</x-primary-button>
                                        </div>
                                    @else
                                        <div class="lg:col-span-8 lg:text-end"><x-status :value="$item->status" /></div>
                                    @endif
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @empty
                <div class="rounded-lg bg-white px-5 py-12 text-center shadow-card">
                    <x-icon name="bx-check-circle" class="text-4xl text-brand-600" />
                    <p class="mt-2 font-medium">{{ $search !== '' ? 'No waiting tests match "'.$search.'".' : 'All caught up.' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $search !== '' ? 'Try a patient number or test name.' : 'New requests appear here as soon as a clinician orders them.' }}</p>
                </div>
            @endforelse
        </div>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-card">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 font-medium">Verified</th>
                        <th class="px-5 py-3 font-medium">Patient</th>
                        <th class="px-5 py-3 font-medium">Test</th>
                        <th class="px-5 py-3 font-medium">Result</th>
                        <th class="px-5 py-3 font-medium">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($results as $item)
                        @php($flagged = in_array($item->result_flag, ['abnormal', 'critical'], true))
                        <tr class="{{ $item->result_flag === 'critical' ? 'bg-red-50/60' : '' }}">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-600">
                                <span class="block">{{ $item->verified_at?->isToday() ? 'Today' : $item->verified_at?->format('d M Y') }}</span>
                                <span class="block text-xs text-slate-400">{{ $item->verified_at?->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('visits.show', $item->request->visit) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $item->request->visit->patient->fullName() }}</a>
                                <span class="block text-xs text-slate-400">{{ $item->request->visit->patient->patient_number }} · {{ $item->request->visit->visit_number }}</span>
                            </td>
                            <td class="px-5 py-3">
                                {{ $item->test->name }}
                                @if ($item->test->reference_text)
                                    <span class="block text-xs text-slate-400">Reference {{ $item->test->reference_text }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="{{ $flagged ? 'font-semibold text-red-700' : 'text-slate-900' }}">{{ $item->result_value }} {{ $item->test->unit }}</span>
                                @if ($flagged)
                                    <span class="ms-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $item->result_flag === 'critical' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-900' }}">{{ ucfirst($item->result_flag) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $item->verifier?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">{{ $search !== '' ? 'No verified results match "'.$search.'".' : 'No results verified yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $results->links() }}</div>
    @endif
</x-app-layout>
