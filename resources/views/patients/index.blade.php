<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-title icon="bx-user">Patients</x-page-title>
            @if (auth()->user()->permits('patients.manage'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 rounded-md bg-brand-700 px-4 py-2 text-sm font-semibold text-white">
                    <x-icon name="bx-user-plus" class="text-lg" />
                    Register patient
                </a>
            @endif
        </div>
    </x-slot>

    <form method="GET" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ $search }}" placeholder="Name, phone, patient number or national ID" class="w-full rounded-md border-slate-300 shadow-sm focus:border-brand-600 focus:ring-brand-600">
        <button class="inline-flex items-center gap-2 rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">
            <x-icon name="bx-search" class="text-lg" />
            Search
        </button>
    </form>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Number</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Sex</th>
                    <th class="px-4 py-3">Age</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $patient)
                    <tr>
                        <td class="px-4 py-3"><a class="text-brand-700" href="{{ route('patients.show', $patient) }}">{{ $patient->patient_number }}</a></td>
                        <td class="px-4 py-3">{{ $patient->fullName() }}</td>
                        <td class="px-4 py-3">{{ $patient->phone }}</td>
                        <td class="px-4 py-3">{{ $patient->sex->label() }}</td>
                        <td class="px-4 py-3">{{ $patient->ageInYears() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-slate-500">No patients match that search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $patients->links() }}</div>
</x-app-layout>
