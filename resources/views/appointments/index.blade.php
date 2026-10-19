<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-calendar">Appointments</x-page-title></x-slot>
    <form method="POST" action="{{ route('appointments.store') }}" class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4">
        @csrf
        <select name="patient_id" required class="rounded-md border-slate-300 text-sm">
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}">{{ $patient->fullName() }}</option>
            @endforeach
        </select>
        <select name="department_id" required class="rounded-md border-slate-300 text-sm">
            @foreach ($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
        <input type="datetime-local" name="scheduled_at" required class="rounded-md border-slate-300 text-sm">
        <input name="reason" placeholder="Reason" class="rounded-md border-slate-300 text-sm">
        <div class="md:col-span-4"><x-primary-button>Book and queue SMS</x-primary-button></div>
    </form>
    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @foreach ($appointments as $appointment)
                    <tr>
                        <td class="px-4 py-3">{{ $appointment->scheduled_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $appointment->patient->fullName() }}</td>
                        <td class="px-4 py-3">{{ $appointment->department->name }}</td>
                        <td class="px-4 py-3">{{ $appointment->status }}</td>
                        <td class="px-4 py-3">
                            @if ($appointment->status === 'booked')
                                <form method="POST" action="{{ route('appointments.check-in', $appointment) }}">@csrf<button class="text-brand-700">Check in</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $appointments->links() }}</div>
</x-app-layout>
