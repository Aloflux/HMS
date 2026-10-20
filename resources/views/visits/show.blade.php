<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-page-title icon="bx-clipboard">{{ $visit->visit_number }}</x-page-title>
                <p class="text-sm text-slate-500">
                    <a class="text-brand-700" href="{{ route('patients.show', $visit->patient) }}">{{ $visit->patient->fullName() }}</a>
                    · {{ $visit->visit_type->label() }} · {{ $visit->payer->label() }} · {{ $visit->status->label() }}
                </p>
            </div>
            <p class="text-sm">Cash balance <span class="font-semibold">{{ \App\Support\Money::format($visit->bill?->cashBalance() ?? 0) }}</span></p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-2">
        @if (auth()->user()->permits('triage.manage'))
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Triage</h2>
                @foreach ($visit->triageRecords as $record)
                    <p class="mt-2 text-sm text-slate-600">{{ $record->created_at->format('d M H:i') }} · {{ $record->priority->label() }} · {{ $record->chief_complaint }} · {{ $record->temperature }}°C · {{ $record->systolic }}/{{ $record->diastolic }}</p>
                @endforeach
                <form method="POST" action="{{ route('visits.triage', $visit) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <input name="temperature" placeholder="Temp °C" class="rounded-md border-slate-300 text-sm">
                    <input name="pulse" placeholder="Pulse" class="rounded-md border-slate-300 text-sm">
                    <input name="respiration" placeholder="Resp" class="rounded-md border-slate-300 text-sm">
                    <input name="spo2" placeholder="SpO2" class="rounded-md border-slate-300 text-sm">
                    <input name="systolic" placeholder="Systolic" class="rounded-md border-slate-300 text-sm">
                    <input name="diastolic" placeholder="Diastolic" class="rounded-md border-slate-300 text-sm">
                    <input name="weight_kg" placeholder="Weight kg" class="rounded-md border-slate-300 text-sm">
                    <input name="height_cm" placeholder="Height cm" class="rounded-md border-slate-300 text-sm">
                    <select name="priority" class="rounded-md border-slate-300 text-sm">
                        <option value="routine">Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                    <input name="chief_complaint" placeholder="Chief complaint" required class="rounded-md border-slate-300 text-sm">
                    <textarea name="notes" placeholder="Notes" class="rounded-md border-slate-300 text-sm sm:col-span-2"></textarea>
                    <div class="sm:col-span-2"><x-primary-button>Save triage</x-primary-button></div>
                </form>
            </section>
        @endif

        @if (auth()->user()->permits('consultations.manage'))
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Consultation</h2>
                @foreach ($visit->consultations as $consultation)
                    <div class="mt-3 text-sm">
                        <p class="font-medium">{{ $consultation->clinician?->name }} · {{ $consultation->completed_at?->format('d M H:i') }}</p>
                        <p>{{ $consultation->plan }}</p>
                        <p class="text-slate-500">{{ $consultation->diagnoses->pluck('icd10_code')->join(', ') }}</p>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('visits.consult', $visit) }}" class="mt-4 grid gap-3">
                    @csrf
                    <textarea name="history" placeholder="History" class="rounded-md border-slate-300 text-sm"></textarea>
                    <textarea name="examination" placeholder="Examination" class="rounded-md border-slate-300 text-sm"></textarea>
                    <textarea name="plan" placeholder="Plan" class="rounded-md border-slate-300 text-sm"></textarea>
                    <textarea name="referral" placeholder="Referral letter" class="rounded-md border-slate-300 text-sm"></textarea>
                    <select name="icd10_codes[]" multiple required class="h-32 rounded-md border-slate-300 text-sm">
                        @foreach ($icd10 as $code)
                            <option value="{{ $code->code }}">{{ $code->code }} {{ $code->title }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Save consultation</x-primary-button>
                </form>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Orders</h2>
                <form method="POST" action="{{ route('visits.labs', $visit) }}" class="mt-3 grid gap-2">
                    @csrf
                    <select name="test_ids[]" multiple class="h-24 rounded-md border-slate-300 text-sm">
                        @foreach ($labTests as $test)
                            <option value="{{ $test->id }}">{{ $test->name }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Order labs</x-primary-button>
                </form>
                <form method="POST" action="{{ route('visits.imaging', $visit) }}" class="mt-4 grid gap-2">
                    @csrf
                    <select name="imaging_study_id" class="rounded-md border-slate-300 text-sm">
                        @foreach ($studies as $study)
                            <option value="{{ $study->id }}">{{ $study->name }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Order imaging</x-primary-button>
                </form>
                <form method="POST" action="{{ route('visits.prescriptions', $visit) }}" class="mt-4 grid gap-3">
                    @csrf
                    <div>
                        <x-input-label for="prescription-item" value="Medicine" />
                        <select id="prescription-item" name="lines[0][item_id]" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600">
                            @foreach ($medicines as $medicine)
                                <option value="{{ $medicine->id }}">{{ $medicine->name }} · {{ $medicine->unit }} · {{ \App\Support\Money::format($medicine->cash_price) }} each</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <x-input-label for="prescription-dose" value="Dose" />
                            <x-text-input id="prescription-dose" name="lines[0][dose]" class="mt-1 block w-full" placeholder="1 g" required />
                        </div>
                        <div>
                            <x-input-label for="prescription-frequency" value="Frequency" />
                            <x-text-input id="prescription-frequency" name="lines[0][frequency]" class="mt-1 block w-full" placeholder="Three times a day" required />
                        </div>
                        <div>
                            <x-input-label for="prescription-duration" value="Duration (days)" />
                            <x-text-input id="prescription-duration" name="lines[0][duration_days]" type="number" min="1" value="3" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="prescription-quantity" value="Quantity to dispense" />
                            <x-text-input id="prescription-quantity" name="lines[0][quantity]" type="number" min="1" value="1" class="mt-1 block w-full" required />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Writing a prescription checks the price and does not move stock.</p>
                    <x-primary-button>Prescribe</x-primary-button>
                </form>
            </section>
        @endif

        <section class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Bill</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($visit->bill?->lines ?? [] as $line)
                    <li class="flex justify-between py-2">
                        <span>{{ $line->description }} × {{ $line->quantity }}</span>
                        <span>{{ \App\Support\Money::format($line->amount) }}</span>
                    </li>
                @empty
                    <li class="py-3 text-slate-500">No charges yet.</li>
                @endforelse
            </ul>
            @if (auth()->user()->permits('consultations.manage') || auth()->user()->permits('cashier.manage') || auth()->user()->permits('wards.manage'))
                <form method="POST" action="{{ route('visits.complete', $visit) }}" class="mt-4">
                    @csrf
                    <x-primary-button>Complete outpatient visit</x-primary-button>
                </form>
            @endif
        </section>

        @if (auth()->user()->permits('wards.manage') && ! $visit->admission)
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Admit</h2>
                <form method="POST" action="{{ route('visits.admit', $visit) }}" class="mt-3 grid gap-2">
                    @csrf
                    <select name="bed_id" class="rounded-md border-slate-300 text-sm" required>
                        @foreach ($beds as $bed)
                            <option value="{{ $bed->id }}">{{ $bed->ward->name }} {{ $bed->code }}</option>
                        @endforeach
                    </select>
                    <input name="diagnosis_summary" placeholder="Working diagnosis" required class="rounded-md border-slate-300 text-sm">
                    <x-primary-button>Admit</x-primary-button>
                </form>
            </section>
        @endif

        @if ($visit->labRequests->isNotEmpty())
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Laboratory</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($visit->labRequests as $request)
                        @foreach ($request->items as $item)
                            <li class="flex items-center justify-between gap-3">
                                <span>{{ $item->test->name }} · {{ $item->status }} {{ $item->result_value }}</span>
                                @if ($item->status === 'ordered' && auth()->user()->permits('consultations.manage'))
                                    <form method="POST" action="{{ route('lab.cancel', $item) }}">@csrf<button class="text-xs text-red-700">Cancel</button></form>
                                @endif
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-app-layout>
