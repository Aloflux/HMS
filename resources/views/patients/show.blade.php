<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Patient directory</x-page-title>
            <p class="mt-1 text-sm text-slate-500">{{ $currentFacility->name ?? 'Platform' }}</p>
        </div>
    </x-slot>

    <section class="rounded-lg bg-white shadow-sm">
        <div class="flex items-center gap-4 border-b border-slate-100 px-5 py-5">
            @if ($patient->photo_path)
                <img src="{{ route('patients.photo', $patient) }}" alt="Photo of {{ $patient->fullName() }}" class="h-14 w-14 rounded-full object-cover">
            @else
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-base font-semibold text-brand-800">{{ $patient->initials() }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-semibold tracking-tight">{{ $patient->fullName() }}</h2>
                <p class="text-sm text-slate-500">{{ $patient->patient_number }}@if ($patient->national_id) · {{ $patient->national_id }}@endif</p>
            </div>
            @if (auth()->user()->permits('patients.manage'))
                <a href="{{ route('patients.edit', $patient) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:text-brand-700" aria-label="Edit {{ $patient->fullName() }}">
                    <x-icon name="bx-edit" class="text-xl" />
                </a>
            @endif
        </div>

        <div class="grid gap-6 px-5 py-5 lg:grid-cols-3">
            <div class="space-y-6">
                <div>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Demographics</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Sex</dt><dd class="font-medium">{{ $patient->sex->label() }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Age</dt><dd class="text-end font-medium">{{ $patient->ageInYears() }} yrs ({{ $patient->date_of_birth->format('d M Y') }})</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $patient->phone }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Residence</dt><dd class="text-end font-medium">{{ trim($patient->village.' '.$patient->district) }}</dd></div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Next of kin</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="text-end font-medium">{{ $patient->next_of_kin_name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Relationship</dt><dd class="font-medium">{{ $patient->next_of_kin_relationship }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $patient->next_of_kin_phone }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Clinical brief</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Blood group</dt><dd class="font-medium text-brand-800">{{ $patient->blood_group ?: 'Not recorded' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Allergies</dt><dd class="text-end font-medium {{ $patient->allergies ? 'text-red-700' : '' }}">{{ $patient->allergies ?: 'None recorded' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Last visit</dt><dd class="text-end font-medium">{{ $latestVisit ? $latestVisit->opened_at->format('d M Y').' · '.$latestVisit->visit_type->label() : 'No visit' }}</dd></div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Active orders</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ($labOrders as $order)
                            <li class="rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-900">{{ $order->test?->name ?? 'Laboratory test' }} · result pending</li>
                        @endforeach
                        @foreach ($pendingMedicines as $line)
                            <li class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $line->item?->name ?? 'Medicine' }} · pending dispense</li>
                        @endforeach
                        @if ($labOrders->isEmpty() && $pendingMedicines->isEmpty())
                            <li class="text-sm text-slate-500">No laboratory or pharmacy orders are waiting.</li>
                        @endif
                    </ul>
                </div>
            </div>

            <div>
                <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Visit history</h3>
                <ul class="mt-3 space-y-3">
                    @forelse ($patient->visits->take(5) as $visit)
                        <li class="flex gap-3 text-sm">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-600"></span>
                            <span>
                                <a href="{{ route('visits.show', $visit) }}" class="font-medium text-slate-900">{{ $visit->opened_at->format('d M Y') }} · {{ $visit->visit_type->label() }}</a>
                                <span class="block text-slate-500">{{ $visit->chief_complaint ?: $visit->status->label() }}{{ $visit->clinician ? ' · '.$visit->clinician->name : '' }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No visits yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    @if (auth()->user()->permits('patients.manage'))
        <section class="mt-4 rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Open a visit</h2>
            <form method="POST" action="{{ route('visits.store', $patient) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <x-input-label for="visit_type" value="Visit type" />
                    <select id="visit_type" name="visit_type" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                        <option value="outpatient">Outpatient</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="priority" value="Priority" />
                    <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                        <option value="routine">Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="payer" value="Payer" />
                    <select id="payer" name="payer" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                        <option value="cash">Cash</option>
                        <option value="insurance">Insurance</option>
                        <option value="corporate">Corporate</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="department_id" value="Department" />
                    <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                        <option value="">Choose a department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="insurance_scheme_id" value="Insurance scheme" />
                    <select id="insurance_scheme_id" name="insurance_scheme_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                        <option value="">Only when the payer is not cash</option>
                        @foreach ($schemes as $scheme)
                            <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="member_number" value="Member number" />
                    <x-text-input id="member_number" name="member_number" class="mt-1 block w-full" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="chief_complaint" value="Chief complaint" />
                    <x-text-input id="chief_complaint" name="chief_complaint" class="mt-1 block w-full" />
                </div>
                <div class="sm:col-span-2"><x-primary-button>Open visit</x-primary-button></div>
            </form>
        </section>
    @endif

    <section class="mt-4 rounded-lg bg-white shadow-sm">
        <div class="px-5 pt-5">
            <h2 class="font-semibold">Recently registered</h2>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Patient</th>
                        <th class="px-5 py-3 font-medium">Phone</th>
                        <th class="px-5 py-3 font-medium">Residence</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentPatients as $recent)
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('patients.show', $recent) }}" class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-800">{{ $recent->initials() }}</span>
                                    <span>
                                        <span class="block font-medium text-slate-900">{{ $recent->fullName() }}</span>
                                        <span class="block text-xs text-slate-400">{{ $recent->patient_number }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $recent->phone }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $recent->district }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-medium uppercase tracking-wide text-brand-800">{{ $recent->latestVisit?->visit_type->label() ?? 'Registered' }}</span>
                            </td>
                            <td class="px-5 py-3 text-end">
                                <a href="{{ route('patients.show', $recent) }}" class="text-slate-400 hover:text-brand-700" aria-label="Open {{ $recent->fullName() }}">
                                    <x-icon name="bx-chevron-right" class="text-xl" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-slate-500">No other patients are on this register.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
