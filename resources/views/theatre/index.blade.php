<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-plus-medical">Theatre</x-page-title></x-slot>
    <form method="POST" action="{{ route('theatre.store') }}" class="mb-6 grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4">
        @csrf
        <select name="patient_id" class="rounded-md border-slate-300 text-sm">@foreach ($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->fullName() }}</option>@endforeach</select>
        <input name="procedure" required placeholder="Procedure" class="rounded-md border-slate-300 text-sm">
        <input type="datetime-local" name="scheduled_at" required class="rounded-md border-slate-300 text-sm">
        <x-primary-button>Book</x-primary-button>
    </form>
    @foreach ($bookings as $booking)
        <section class="mb-4 rounded-lg bg-white p-4 shadow-sm">
            <p class="font-medium">{{ $booking->procedure }} · {{ $booking->patient->fullName() }} · {{ $booking->scheduled_at->format('d M Y H:i') }} · {{ $booking->status }}</p>
            @if ($booking->note)
                <p class="mt-2 text-sm">{{ $booking->note->procedure_notes }}</p>
            @else
                <form method="POST" action="{{ route('theatre.notes', $booking) }}" class="mt-3 grid gap-2">
                    @csrf
                    <textarea name="findings" placeholder="Findings" class="rounded-md border-slate-300 text-sm"></textarea>
                    <textarea name="procedure_notes" required placeholder="Operation notes" class="rounded-md border-slate-300 text-sm"></textarea>
                    <x-primary-button>Save operation note</x-primary-button>
                </form>
            @endif
        </section>
    @endforeach
</x-app-layout>
