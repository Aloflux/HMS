<x-app-layout>
    <x-slot name="header"><x-page-title>Theatre</x-page-title></x-slot>

    <datalist id="theatre-patients">
        @foreach ($patients as $patient)
            <option value="{{ $patient->patient_number }}">{{ $patient->fullName() }}</option>
        @endforeach
    </datalist>

    <form method="POST" action="{{ route('theatre.store') }}" class="mb-6 rounded-lg bg-white p-5 shadow-card">
        @csrf
        <h2 class="font-bold text-slate-900">Book theatre</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-4">
            <div>
                <x-input-label for="theatre-patient" value="Patient number" />
                <x-text-input id="theatre-patient" name="patient_number" list="theatre-patients" class="mt-1 block w-full" :value="old('patient_number')" placeholder="Type a number or name" required />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="procedure" value="Procedure" />
                <x-text-input id="procedure" name="procedure" class="mt-1 block w-full" :value="old('procedure')" required />
            </div>
            <div>
                <x-input-label for="scheduled-at" value="When" />
                <x-text-input id="scheduled-at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full" :value="old('scheduled_at')" required />
            </div>
        </div>
        <div class="mt-4"><x-primary-button>Book</x-primary-button></div>
    </form>

    <div class="grid gap-4">
        @forelse ($bookings as $booking)
            <section class="rounded-lg bg-white p-5 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $booking->procedure }}</p>
                        <p class="text-sm text-slate-500">
                            <a href="{{ route('patients.show', $booking->patient) }}" class="text-brand-700">{{ $booking->patient->fullName() }}</a>
                            · {{ $booking->scheduled_at->format('D d M Y H:i') }} · {{ $booking->surgeon?->name }}
                        </p>
                    </div>
                    <x-status :value="$booking->status" />
                </div>
                @if ($booking->note)
                    @if ($booking->note->findings)
                        <p class="mt-3 text-sm"><span class="font-medium">Findings:</span> {{ $booking->note->findings }}</p>
                    @endif
                    <p class="mt-2 whitespace-pre-line text-sm">{{ $booking->note->procedure_notes }}</p>
                @else
                    <form method="POST" action="{{ route('theatre.notes', $booking) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4">
                        @csrf
                        <div>
                            <x-input-label :for="'findings-'.$booking->id" value="Findings" />
                            <textarea id="findings-{{ $booking->id }}" name="findings" rows="2" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm"></textarea>
                        </div>
                        <div>
                            <x-input-label :for="'notes-'.$booking->id" value="Operation notes" />
                            <textarea id="notes-{{ $booking->id }}" name="procedure_notes" rows="3" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm"></textarea>
                        </div>
                        <div><x-primary-button>Save operation note</x-primary-button></div>
                    </form>
                @endif
            </section>
        @empty
            <p class="text-sm text-slate-500">No theatre bookings yet.</p>
        @endforelse
    </div>
</x-app-layout>
