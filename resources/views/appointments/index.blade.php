<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-calendar">Appointments</x-page-title></x-slot>

    <form
        method="POST"
        action="{{ route('appointments.store') }}"
        class="mb-6 rounded-lg bg-white p-5 shadow-sm"
        x-data="{
            mode: @js(old('booking_for', 'existing')),
            query: '',
            patientId: @js((string) old('patient_id', '')),
            patients: @js($patientChoices),
            choose(patient) {
                this.patientId = String(patient.id);
                this.query = '';
            },
            get selected() {
                return this.patients.find((patient) => String(patient.id) === String(this.patientId)) ?? null;
            },
            get matches() {
                const query = this.query.trim().toLowerCase();
                if (query.length < 2) {
                    return [];
                }
                return this.patients.filter((patient) => {
                    return `${patient.name} ${patient.phone} ${patient.number}`.toLowerCase().includes(query);
                }).slice(0, 8);
            },
        }"
    >
        @csrf
        <input type="hidden" name="booking_for" :value="mode">

        <div class="flex flex-wrap gap-2">
            <button type="button" @click="mode = 'existing'" :aria-pressed="mode === 'existing'" class="rounded-xl px-4 py-2 text-sm font-semibold" :class="mode === 'existing' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-700'">Already registered</button>
            <button type="button" @click="mode = 'new'" :aria-pressed="mode === 'new'" class="rounded-xl px-4 py-2 text-sm font-semibold" :class="mode === 'new' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-700'">New patient</button>
        </div>
        <p class="mt-3 text-sm text-slate-600" x-show="mode === 'existing'">Type a name, phone, or patient number, then choose the visit time.</p>
        <p class="mt-3 text-sm text-slate-600" x-show="mode === 'new'" x-cloak>Register the patient and book the visit in this one step. A fuller record can still be opened afterwards.</p>

        <div class="mt-4" x-show="mode === 'existing'">
            <x-input-label for="patient_search" value="Patient" />
            <input id="patient_search" type="search" x-model="query" placeholder="Name, phone, or patient number" class="mt-1 block w-full rounded-md border border-slate-300 shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none" autocomplete="off">
            <input type="hidden" name="patient_id" :value="patientId" :disabled="mode !== 'existing'">
            <x-input-error :messages="$errors->get('patient_id')" class="mt-2" />

            <p class="mt-3 text-sm text-slate-800" x-show="selected" x-cloak>
                <span class="font-semibold" x-text="selected ? `${selected.name} · ${selected.number} · ${selected.phone}` : ''"></span>
                <button type="button" class="ml-2 text-brand-700" @click="patientId = ''">Change</button>
            </p>

            <ul class="mt-2 overflow-hidden rounded-lg border border-slate-200" x-show="matches.length" x-cloak>
                <template x-for="patient in matches" :key="patient.id">
                    <li class="border-b border-slate-100 last:border-b-0">
                        <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-50" @click="choose(patient)">
                            <span x-text="patient.name"></span>
                            <span class="text-slate-500" x-text="`${patient.number} · ${patient.phone}`"></span>
                        </button>
                    </li>
                </template>
            </ul>
            <p class="mt-2 text-sm text-slate-500" x-show="query.trim().length >= 2 && matches.length === 0" x-cloak>
                No one on the register matches.
                <button type="button" class="font-semibold text-brand-700" @click="mode = 'new'">Register them here</button>
            </p>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="mode === 'new'" x-cloak>
            <div>
                <x-input-label for="surname" value="Surname" />
                <x-text-input id="surname" name="surname" class="mt-1 block w-full" :value="old('surname')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('surname')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="given_name" value="Given name" />
                <x-text-input id="given_name" name="given_name" class="mt-1 block w-full" :value="old('given_name')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('given_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="sex" value="Sex" />
                <select id="sex" name="sex" class="mt-1 block w-full rounded-md border-slate-300 shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'">
                    <option value="female" @selected(old('sex', 'female') === 'female')>Female</option>
                    <option value="male" @selected(old('sex') === 'male')>Male</option>
                </select>
                <x-input-error :messages="$errors->get('sex')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="date_of_birth" value="Date of birth" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="national_id" value="National ID" />
                <x-text-input id="national_id" name="national_id" class="mt-1 block w-full" :value="old('national_id')" x-bind:disabled="mode !== 'new'" />
                <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="district" value="District" />
                <x-text-input id="district" name="district" class="mt-1 block w-full" :value="old('district')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('district')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="next_of_kin_name" value="Next of kin" />
                <x-text-input id="next_of_kin_name" name="next_of_kin_name" class="mt-1 block w-full" :value="old('next_of_kin_name')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('next_of_kin_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="next_of_kin_relationship" value="Relationship" />
                <x-text-input id="next_of_kin_relationship" name="next_of_kin_relationship" class="mt-1 block w-full" :value="old('next_of_kin_relationship')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('next_of_kin_relationship')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="next_of_kin_phone" value="Next of kin phone" />
                <x-text-input id="next_of_kin_phone" name="next_of_kin_phone" class="mt-1 block w-full" :value="old('next_of_kin_phone')" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" />
                <x-input-error :messages="$errors->get('next_of_kin_phone')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="allergies" value="Known allergies" />
                <textarea id="allergies" name="allergies" class="mt-1 block w-full rounded-md border-slate-300 shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none" x-bind:disabled="mode !== 'new'">{{ old('allergies') }}</textarea>
                <x-input-error :messages="$errors->get('allergies')" class="mt-2" />
            </div>
            <label class="flex items-start gap-2 text-sm sm:col-span-2">
                <input type="checkbox" name="consent" value="1" class="mt-1 rounded border-slate-300 text-brand-700" x-bind:disabled="mode !== 'new'" x-bind:required="mode === 'new'" @checked(old('consent'))>
                <span>The patient (or guardian) consents to this facility keeping their health record under the Data Protection and Privacy Act, 2019.</span>
            </label>
            <x-input-error :messages="$errors->get('consent')" class="sm:col-span-2" />
            <label class="flex items-start gap-2 text-sm sm:col-span-2">
                <input type="checkbox" name="confirm_duplicate" value="1" class="mt-1 rounded border-slate-300 text-brand-700" x-bind:disabled="mode !== 'new'" @checked(old('confirm_duplicate'))>
                <span>Register anyway if the phone, name and date of birth already match a record.</span>
            </label>
            <x-input-error :messages="$errors->get('confirm_duplicate')" class="sm:col-span-2" />
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div>
                <x-input-label for="department_id" value="Department" />
                <select id="department_id" name="department_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="scheduled_at" value="When" />
                <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full" :value="old('scheduled_at')" required />
                <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="reason" value="Reason" />
                <x-text-input id="reason" name="reason" class="mt-1 block w-full" :value="old('reason')" />
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-primary-button>
                <span x-text="mode === 'new' ? 'Register and book' : 'Book appointment'">Book appointment</span>
            </x-primary-button>
        </div>
    </form>

    <div class="mb-3 flex flex-wrap gap-2">
        @foreach (['upcoming' => 'Today and upcoming', 'past' => 'Past'] as $value => $label)
            <a href="{{ route('appointments.index', ['when' => $value]) }}" class="rounded-full px-3 py-1 text-sm {{ $when === $value ? 'bg-brand-700 text-white' : 'bg-white text-slate-700' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 font-medium">When</th>
                    <th class="px-4 py-3 font-medium">Patient</th>
                    <th class="px-4 py-3 font-medium">Department</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($appointments as $appointment)
                    @php
                        $lastVisit = $appointment->patient->latestVisit;
                    @endphp
                    <tr class="align-top">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="block font-medium">{{ $appointment->scheduled_at->isToday() ? 'Today' : $appointment->scheduled_at->format('D d M Y') }}</span>
                            <span class="block text-xs text-slate-500">{{ $appointment->scheduled_at->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('patients.show', $appointment->patient) }}" class="font-medium text-slate-900">{{ $appointment->patient->fullName() }}</a>
                            @if ($appointment->reason)
                                <span class="block text-xs text-slate-500">{{ $appointment->reason }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $appointment->department->name }}</td>
                        <td class="px-4 py-3"><x-status :value="$appointment->status" /></td>
                        <td class="px-4 py-3">
                            @if ($appointment->status === 'booked')
                                <details class="group">
                                    <summary class="cursor-pointer list-none text-sm font-semibold text-brand-700">Check in</summary>
                                    <form
                                        method="POST"
                                        action="{{ route('appointments.check-in', $appointment) }}"
                                        class="mt-2 grid w-64 gap-2"
                                        x-data="{ payer: @js($lastVisit?->payer->value ?? 'cash') }"
                                    >
                                        @csrf
                                        <label class="text-xs text-slate-500" for="payer-{{ $appointment->id }}">Who pays</label>
                                        <select id="payer-{{ $appointment->id }}" name="payer" x-model="payer" class="rounded-md border-slate-300 text-sm">
                                            @foreach ($payers as $payer)
                                                <option value="{{ $payer->value }}">{{ $payer->label() }}</option>
                                            @endforeach
                                        </select>
                                        <template x-if="payer !== 'cash'">
                                            <div class="grid gap-2">
                                                <select name="insurance_scheme_id" class="rounded-md border-slate-300 text-sm" aria-label="Scheme">
                                                    @foreach ($schemes as $scheme)
                                                        <option value="{{ $scheme->id }}" @selected($lastVisit?->insurance_scheme_id === $scheme->id)>{{ $scheme->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="member_number" value="{{ $lastVisit?->member_number }}" placeholder="Member number" aria-label="Member number" class="rounded-md border-slate-300 text-sm">
                                            </div>
                                        </template>
                                        <x-primary-button>Open visit</x-primary-button>
                                    </form>
                                </details>
                                <div class="mt-2 flex gap-3 text-xs">
                                    <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="no_show">
                                        <button class="text-slate-600 hover:text-slate-900">No-show</button>
                                    </form>
                                    <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="text-red-700">Cancel</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-slate-500">{{ $when === 'past' ? 'No past appointments.' : 'Nothing booked from today on.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $appointments->links() }}</div>
</x-app-layout>
