<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title icon="bx-id-card">{{ $patient->fullName() }}</x-page-title>
            <p class="text-sm text-slate-500">{{ $patient->patient_number }} · {{ $patient->sex->label() }} · {{ $patient->ageInYears() }} years · {{ $patient->phone }}</p>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg bg-white p-5 shadow-sm lg:col-span-1">
            @if ($patient->photo_path)
                <img src="{{ route('patients.photo', $patient) }}" alt="Photo of {{ $patient->fullName() }}" class="mb-4 h-40 w-40 rounded-xl object-cover">
            @endif
            <dl class="grid gap-2 text-sm">
                <div><dt class="text-slate-500">District</dt><dd>{{ $patient->district }} {{ $patient->village }}</dd></div>
                <div><dt class="text-slate-500">National ID</dt><dd>{{ $patient->national_id ?: 'Not recorded' }}</dd></div>
                <div><dt class="text-slate-500">Next of kin</dt><dd>{{ $patient->next_of_kin_name }} ({{ $patient->next_of_kin_relationship }}) {{ $patient->next_of_kin_phone }}</dd></div>
                <div><dt class="text-slate-500">Allergies</dt><dd>{{ $patient->allergies ?: 'None recorded' }}</dd></div>
                <div><dt class="text-slate-500">Consent</dt><dd>{{ $patient->consent_at?->format('d M Y H:i') }}</dd></div>
            </dl>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="font-semibold">Open a visit</h2>
            @if (auth()->user()->permits('patients.manage'))
                <form method="POST" action="{{ route('visits.store', $patient) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <select name="visit_type" class="rounded-md border-slate-300 text-sm">
                        <option value="outpatient">Outpatient</option>
                        <option value="emergency">Emergency</option>
                    </select>
                    <select name="priority" class="rounded-md border-slate-300 text-sm">
                        <option value="routine">Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                    <select name="payer" class="rounded-md border-slate-300 text-sm">
                        <option value="cash">Cash</option>
                        <option value="insurance">Insurance</option>
                        <option value="corporate">Corporate</option>
                    </select>
                    <select name="department_id" class="rounded-md border-slate-300 text-sm">
                        <option value="">Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <select name="insurance_scheme_id" class="rounded-md border-slate-300 text-sm">
                        <option value="">Scheme, if not cash</option>
                        @foreach ($schemes as $scheme)
                            <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                        @endforeach
                    </select>
                    <input name="member_number" placeholder="Member number" class="rounded-md border-slate-300 text-sm">
                    <input name="chief_complaint" placeholder="Chief complaint" class="rounded-md border-slate-300 text-sm sm:col-span-2">
                    <div class="sm:col-span-2"><x-primary-button>Open visit</x-primary-button></div>
                </form>
            @endif

            <h2 class="mt-8 font-semibold">Visit history</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($patient->visits as $visit)
                    <li class="flex items-center justify-between py-3">
                        <a class="text-brand-700" href="{{ route('visits.show', $visit) }}">{{ $visit->visit_number }}</a>
                        <span>{{ $visit->opened_at->format('d M Y') }} · {{ $visit->status->label() }}</span>
                    </li>
                @empty
                    <li class="py-4 text-slate-500">No visits yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
