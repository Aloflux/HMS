@php
    $user = auth()->user();
    $open = ! in_array($visit->status, [\App\Enums\VisitStatus::Discharged, \App\Enums\VisitStatus::Closed], true);
    $canComplete = $open
        && $visit->status !== \App\Enums\VisitStatus::Admitted
        && ($user->permits('consultations.manage') || $user->permits('cashier.manage') || $user->permits('wards.manage'));
    $labItems = $visit->labRequests->flatMap->items;
    $prescriptionItems = $visit->prescriptions->flatMap->items;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a class="text-sm text-brand-700" href="{{ route('patients.show', $visit->patient) }}">{{ $visit->patient->fullName() }} · {{ $visit->patient->patient_number }}</a>
                <x-page-title>Visit {{ $visit->visit_number }}</x-page-title>
                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <x-status :value="$visit->status" />
                    {{ $visit->visit_type->label() }} · {{ $visit->payer->label() }}{{ $visit->scheme ? ' ('.$visit->scheme->name.')' : '' }}
                    · opened {{ $visit->opened_at->format('d M Y H:i') }}
                    @if ($visit->patient->allergies)
                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Allergies: {{ $visit->patient->allergies }}</span>
                    @endif
                </p>
            </div>
            <div class="text-end text-sm">
                <span class="block text-slate-500">Cash balance</span>
                <span class="text-xl font-semibold">{{ \App\Support\Money::format($visit->bill?->cashBalance() ?? 0) }}</span>
            </div>
        </div>
    </x-slot>

    @unless ($open)
        <div class="mb-6 rounded-md bg-slate-200/60 px-4 py-3 text-sm text-slate-700">
            This visit is {{ $visit->status->label() }}{{ $visit->closed_at ? ' since '.$visit->closed_at->format('d M Y H:i') : '' }}. It cannot take new orders.
            @if ($user->permits('patients.manage'))
                <a class="font-semibold text-brand-700" href="{{ route('patients.show', $visit->patient) }}#open-visit">Open a new visit</a>
            @endif
        </div>
    @endunless

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="grid content-start gap-6 xl:col-span-2">
            {{-- Clinical record: everyone who can open the visit can read it. --}}
            <section class="rounded-lg bg-white p-5 shadow-card">
                <h2 class="font-bold text-slate-900">Triage</h2>
                @forelse ($visit->triageRecords->sortByDesc('created_at') as $record)
                    <div class="mt-3 border-t border-slate-100 pt-3 text-sm first:border-0 first:pt-0">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ $record->chief_complaint }}</span>
                            <span class="{{ $record->priority === \App\Enums\Priority::Emergency ? 'text-red-700 font-semibold' : 'text-slate-500' }}">{{ $record->priority->label() }}</span>
                        </p>
                        <dl class="mt-2 grid grid-cols-3 gap-2 text-slate-600 sm:grid-cols-6">
                            @foreach (['Temp' => $record->temperature ? $record->temperature.' °C' : null, 'Pulse' => $record->pulse, 'Resp' => $record->respiration, 'BP' => $record->systolic ? $record->systolic.'/'.$record->diastolic : null, 'SpO₂' => $record->spo2 ? $record->spo2.'%' : null, 'Weight' => $record->weight_kg ? $record->weight_kg.' kg' : null] as $label => $value)
                                <div><dt class="text-xs text-slate-400">{{ $label }}</dt><dd>{{ $value ?? '—' }}</dd></div>
                            @endforeach
                        </dl>
                        @if ($record->notes)
                            <p class="mt-2 text-slate-600">{{ $record->notes }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-400">{{ $record->recorder?->name }} · {{ $record->created_at->format('d M H:i') }}</p>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-slate-500">Not triaged yet.</p>
                @endforelse

                @if ($open && $user->permits('triage.manage'))
                    <details class="mt-4 border-t border-slate-100 pt-4" @if ($visit->triageRecords->isEmpty()) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-brand-700">Record vital signs</summary>
                        <form method="POST" action="{{ route('visits.triage', $visit) }}" class="mt-3 grid gap-3 sm:grid-cols-4">
                            @csrf
                            @foreach ([['temperature', 'Temperature (°C)', '0.1'], ['pulse', 'Pulse (bpm)', '1'], ['respiration', 'Respiration (/min)', '1'], ['spo2', 'SpO₂ (%)', '1'], ['systolic', 'Systolic (mmHg)', '1'], ['diastolic', 'Diastolic (mmHg)', '1'], ['weight_kg', 'Weight (kg)', '0.1'], ['height_cm', 'Height (cm)', '0.1']] as [$field, $label, $step])
                                <div>
                                    <x-input-label :for="'triage-'.$field" :value="$label" />
                                    <x-text-input :id="'triage-'.$field" :name="$field" type="number" :step="$step" class="mt-1 block w-full" :value="old($field)" />
                                </div>
                            @endforeach
                            <div class="sm:col-span-3">
                                <x-input-label for="triage-complaint" value="Chief complaint" />
                                <x-text-input id="triage-complaint" name="chief_complaint" class="mt-1 block w-full" :value="old('chief_complaint', $visit->chief_complaint)" required />
                            </div>
                            <div>
                                <x-input-label for="triage-priority" value="Priority" />
                                <select id="triage-priority" name="priority" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                    @foreach (\App\Enums\Priority::cases() as $priority)
                                        <option value="{{ $priority->value }}" @selected(old('priority', $visit->priority->value) === $priority->value)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                <x-input-label for="triage-notes" value="Notes" />
                                <textarea id="triage-notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">{{ old('notes') }}</textarea>
                            </div>
                            <div class="sm:col-span-4"><x-primary-button>Save triage</x-primary-button></div>
                        </form>
                    </details>
                @endif
            </section>

            <section class="rounded-lg bg-white p-5 shadow-card">
                <h2 class="font-bold text-slate-900">Consultation</h2>
                @forelse ($visit->consultations as $consultation)
                    <div class="mt-3 border-t border-slate-100 pt-3 text-sm first:border-0 first:pt-0">
                        <p class="text-xs text-slate-400">{{ $consultation->clinician?->name }} · {{ $consultation->completed_at?->format('d M H:i') }}</p>
                        <dl class="mt-2 grid gap-2">
                            @foreach (['History' => $consultation->history, 'Examination' => $consultation->examination, 'Plan' => $consultation->plan, 'Referral' => $consultation->referral] as $label => $value)
                                @if ($value)
                                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="whitespace-pre-line">{{ $value }}</dd></div>
                                @endif
                            @endforeach
                        </dl>
                        <ul class="mt-2 flex flex-wrap gap-2">
                            @foreach ($consultation->diagnoses as $diagnosis)
                                <li class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $diagnosis->icd10_code }} {{ $diagnosis->title }}{{ $diagnosis->rank === 'primary' ? ' · primary' : '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-slate-500">No consultation yet.</p>
                @endforelse

                @if ($open && $user->permits('consultations.manage'))
                    <details class="mt-4 border-t border-slate-100 pt-4" @if ($visit->consultations->isEmpty()) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-brand-700">Write consultation</summary>
                        <form method="POST" action="{{ route('visits.consult', $visit) }}" class="mt-3 grid gap-3" x-data="{ filter: '' }">
                            @csrf
                            @foreach (['history' => 'History', 'examination' => 'Examination', 'plan' => 'Plan', 'referral' => 'Referral letter'] as $field => $label)
                                <div>
                                    <x-input-label :for="'consult-'.$field" :value="$label" />
                                    <textarea id="consult-{{ $field }}" name="{{ $field }}" rows="2" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">{{ old($field) }}</textarea>
                                </div>
                            @endforeach
                            <fieldset>
                                <legend class="text-sm font-medium text-slate-700">Diagnoses <span class="font-normal text-slate-500">· the first ticked is the primary</span></legend>
                                <input type="search" x-model="filter" placeholder="Filter by code or name" aria-label="Filter diagnoses" class="mt-1 w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm sm:max-w-sm">
                                <div class="mt-2 grid max-h-56 gap-1 overflow-y-auto rounded-md bg-slate-50 p-2 sm:grid-cols-2">
                                    @foreach ($icd10 as $code)
                                        <label class="flex items-start gap-2 rounded px-1 py-0.5 text-sm hover:bg-slate-50" x-show="@js(strtolower($code->code.' '.$code->title)).includes(filter.toLowerCase())">
                                            <input type="checkbox" name="icd10_codes[]" value="{{ $code->code }}" class="mt-0.5 rounded border-slate-300 focus:ring-brand-500 text-brand-700" @checked(in_array($code->code, old('icd10_codes', []), true))>
                                            <span><span class="font-medium">{{ $code->code }}</span> {{ $code->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div><x-primary-button>Save consultation</x-primary-button></div>
                        </form>
                    </details>
                @endif
            </section>

            <section class="rounded-lg bg-white p-5 shadow-card">
                <h2 class="font-bold text-slate-900">Results</h2>
                @if ($labItems->isEmpty() && $visit->imagingOrders->isEmpty())
                    <p class="mt-2 text-sm text-slate-500">No tests ordered.</p>
                @endif
                <ul class="mt-2 divide-y divide-slate-100 text-sm">
                    @foreach ($labItems as $item)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                            <span>
                                <span class="font-medium">{{ $item->test->name }}</span>
                                @if ($item->result_value)
                                    · <span class="{{ in_array($item->result_flag, ['abnormal', 'critical'], true) ? 'font-semibold text-red-700' : '' }}">{{ $item->result_value }} {{ $item->test->unit }}</span>
                                    @if (in_array($item->result_flag, ['abnormal', 'critical'], true))
                                        <span class="text-xs font-semibold uppercase text-red-700">{{ $item->result_flag }}</span>
                                    @endif
                                @endif
                                @if ($item->test->reference_text)
                                    <span class="block text-xs text-slate-400">Reference {{ $item->test->reference_text }}</span>
                                @endif
                            </span>
                            <span class="flex items-center gap-3">
                                <x-status :value="$item->status" />
                                @if ($open && $item->status === 'ordered' && $user->permits('consultations.manage'))
                                    <form method="POST" action="{{ route('lab.cancel', $item) }}">@csrf<button class="text-xs text-red-700">Cancel</button></form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                    @foreach ($visit->imagingOrders as $order)
                        <li class="py-2">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="font-medium">{{ $order->study->name }}</span>
                                <x-status :value="$order->status" />
                            </div>
                            @if ($order->report)
                                <p class="mt-1 whitespace-pre-line text-slate-600">{{ $order->report }}</p>
                            @endif
                            @if ($order->image_path && $user->permits('radiology.view'))
                                <a href="{{ route('radiology.image', $order) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs text-brand-700"><x-icon name="bx-image" /> View image</a>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($open && $user->permits('consultations.manage'))
                    <div class="mt-4 grid gap-6 border-t border-slate-100 pt-4 lg:grid-cols-2">
                        <form method="POST" action="{{ route('visits.labs', $visit) }}" x-data="{ filter: '' }">
                            @csrf
                            <h3 class="text-sm font-semibold">Order lab tests</h3>
                            @if ($labTests->isEmpty())
                                <p class="mt-2 text-sm text-slate-500">No lab tests are on the price list yet. An administrator adds them under Price list.</p>
                            @else
                                <input type="search" x-model="filter" placeholder="Filter tests" aria-label="Filter lab tests" class="mt-2 w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                <div class="mt-2 grid max-h-40 gap-1 overflow-y-auto rounded-md bg-slate-50 p-2">
                                    @foreach ($labTests as $test)
                                        <label class="flex items-center justify-between gap-2 rounded px-1 py-0.5 text-sm hover:bg-slate-50" x-show="@js(strtolower($test->name)).includes(filter.toLowerCase())">
                                            <span class="flex items-center gap-2">
                                                <input type="checkbox" name="test_ids[]" value="{{ $test->id }}" class="rounded border-slate-300 focus:ring-brand-500 text-brand-700">
                                                {{ $test->name }}
                                            </span>
                                            <span class="text-xs text-slate-400">{{ \App\Support\Money::format($test->service?->cash_price ?? 0) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-label for="lab-notes" value="Clinical notes" class="mt-2" />
                                <x-text-input id="lab-notes" name="clinical_notes" class="mt-1 block w-full" />
                                <div class="mt-3"><x-primary-button>Order tests</x-primary-button></div>
                            @endif
                        </form>

                        <form method="POST" action="{{ route('visits.imaging', $visit) }}">
                            @csrf
                            <h3 class="text-sm font-semibold">Order imaging</h3>
                            @if ($studies->isEmpty())
                                <p class="mt-2 text-sm text-slate-500">No imaging studies are on the price list yet. An administrator adds them under Price list.</p>
                            @else
                                <x-input-label for="imaging-study" value="Study" class="mt-2" />
                                <select id="imaging-study" name="imaging_study_id" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                    @foreach ($studies as $study)
                                        <option value="{{ $study->id }}">{{ $study->name }} · {{ \App\Support\Money::format($study->service?->cash_price ?? 0) }}</option>
                                    @endforeach
                                </select>
                                <x-input-label for="imaging-notes" value="Clinical notes" class="mt-2" />
                                <x-text-input id="imaging-notes" name="clinical_notes" class="mt-1 block w-full" />
                                <div class="mt-3"><x-primary-button>Order imaging</x-primary-button></div>
                            @endif
                        </form>
                    </div>
                @endif
            </section>

            <section class="rounded-lg bg-white p-5 shadow-card">
                <h2 class="font-bold text-slate-900">Prescriptions</h2>
                <ul class="mt-2 divide-y divide-slate-100 text-sm">
                    @forelse ($prescriptionItems as $line)
                        @php($given = (int) $line->dispensations->sum('quantity'))
                        <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                            <span>
                                <span class="font-medium">{{ $line->item->name }}</span> · {{ $line->dose }} · {{ $line->frequency }} · {{ $line->duration_days }} days
                                <span class="block text-xs text-slate-400">{{ $given }} of {{ $line->quantity }} {{ \Illuminate\Support\Str::plural($line->item->unit, $line->quantity) }} dispensed</span>
                            </span>
                            <x-status :value="$line->status" />
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">No prescriptions.</li>
                    @endforelse
                </ul>

                @if ($open && $user->permits('consultations.manage'))
                    @if ($medicines->isEmpty())
                        <p class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-500">No medicines are on the price list yet.</p>
                    @else
                        <form
                            method="POST"
                            action="{{ route('visits.prescribe', $visit) }}"
                            class="mt-4 border-t border-slate-100 pt-4"
                            x-data="{ lines: [{ key: 1 }], next: 2 }"
                        >
                            @csrf
                            <h3 class="text-sm font-semibold">Prescribe</h3>
                            <template x-for="(line, index) in lines" :key="line.key">
                                <div class="mt-3 grid gap-3 rounded-md bg-slate-50 p-3 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label class="block text-sm font-medium text-slate-700" :for="'rx-item-' + line.key">Medicine</label>
                                        <select :id="'rx-item-' + line.key" :name="'lines[' + index + '][item_id]'" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                            @foreach ($medicines as $medicine)
                                                <option value="{{ $medicine->id }}">{{ $medicine->name }} · {{ $medicine->unit }} · {{ \App\Support\Money::format($medicine->cash_price) }} each</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700" :for="'rx-dose-' + line.key">Dose</label>
                                        <input :id="'rx-dose-' + line.key" :name="'lines[' + index + '][dose]'" required placeholder="1 g" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700" :for="'rx-frequency-' + line.key">Frequency</label>
                                        <input :id="'rx-frequency-' + line.key" :name="'lines[' + index + '][frequency]'" required placeholder="Three times a day" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" :for="'rx-days-' + line.key">Days</label>
                                        <input :id="'rx-days-' + line.key" :name="'lines[' + index + '][duration_days]'" type="number" min="1" max="90" value="3" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700" :for="'rx-qty-' + line.key">Quantity</label>
                                        <input :id="'rx-qty-' + line.key" :name="'lines[' + index + '][quantity]'" type="number" min="1" max="1000" value="1" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                    </div>
                                    <div class="sm:col-span-6" x-show="lines.length > 1">
                                        <button type="button" class="text-xs text-red-700" @click="lines.splice(index, 1)">Remove this medicine</button>
                                    </div>
                                </div>
                            </template>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <button type="button" class="text-sm font-semibold text-brand-700" @click="lines.push({ key: next++ })">+ Add another medicine</button>
                                <x-primary-button>Prescribe</x-primary-button>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Prescribing does not move stock. The pharmacist dispenses it.</p>
                        </form>
                    @endif
                @endif
            </section>
        </div>

        <aside class="grid content-start gap-6">
            <section class="rounded-lg bg-white p-5 shadow-card">
                <h2 class="font-bold text-slate-900">Bill</h2>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @forelse ($visit->bill?->lines ?? [] as $line)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="{{ $line->waived ? 'text-slate-400 line-through' : '' }}">{{ $line->description }} × {{ $line->quantity }}</span>
                            <span>{{ \App\Support\Money::format($line->amount) }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">No charges yet.</li>
                    @endforelse
                </ul>
                @if ($visit->bill)
                    <dl class="mt-3 grid gap-1 border-t border-slate-100 pt-3 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd>{{ \App\Support\Money::format((int) $visit->bill->payments->where('status', 'confirmed')->sum('amount')) }}</dd></div>
                        @if ((int) $visit->bill->waived_amount > 0)
                            <div class="flex justify-between"><dt class="text-slate-500">Waived</dt><dd>{{ \App\Support\Money::format((int) $visit->bill->waived_amount) }}</dd></div>
                        @endif
                        <div class="flex justify-between font-semibold"><dt>Cash balance</dt><dd>{{ \App\Support\Money::format($visit->bill->cashBalance()) }}</dd></div>
                    </dl>
                @endif
                @if ($canComplete)
                    <form method="POST" action="{{ route('visits.complete', $visit) }}" class="mt-4 border-t border-slate-100 pt-4">
                        @csrf
                        <p class="mb-2 text-xs text-slate-500">The cash balance must be settled and pending prescriptions and lab tests closed first.</p>
                        <x-primary-button>Complete visit</x-primary-button>
                    </form>
                @endif
            </section>

            @if ($visit->admission)
                <section class="rounded-lg bg-white p-5 shadow-card">
                    <h2 class="font-bold text-slate-900">Admission</h2>
                    <p class="mt-2 text-sm">{{ $visit->admission->bed->ward->name }} {{ $visit->admission->bed->code }} · <x-status :value="$visit->admission->status" /></p>
                    @if ($user->permits('wards.view'))
                        <a href="{{ route('wards.show', $visit->admission) }}" class="mt-2 inline-block text-sm font-semibold text-brand-700">Open ward record</a>
                    @endif
                </section>
            @elseif ($open && $user->permits('wards.manage'))
                <section class="rounded-lg bg-white p-5 shadow-card">
                    <h2 class="font-bold text-slate-900">Admit</h2>
                    @if ($beds->isEmpty())
                        <p class="mt-2 text-sm text-slate-500">No bed is free. Beds being cleaned can be marked clean on the Wards page.</p>
                    @else
                        <form method="POST" action="{{ route('visits.admit', $visit) }}" class="mt-3 grid gap-3">
                            @csrf
                            <div>
                                <x-input-label for="admit-bed" value="Bed" />
                                <select id="admit-bed" name="bed_id" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm" required>
                                    @foreach ($beds->groupBy(fn ($bed) => $bed->ward->name) as $ward => $wardBeds)
                                        <optgroup label="{{ $ward }}">
                                            @foreach ($wardBeds as $bed)
                                                <option value="{{ $bed->id }}">{{ $bed->code }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="admit-diagnosis" value="Working diagnosis" />
                                <x-text-input id="admit-diagnosis" name="diagnosis_summary" class="mt-1 block w-full" :value="old('diagnosis_summary')" required />
                            </div>
                            <div><x-primary-button>Admit</x-primary-button></div>
                        </form>
                    @endif
                </section>
            @endif
        </aside>
    </div>
</x-app-layout>
