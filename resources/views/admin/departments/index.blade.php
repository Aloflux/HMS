<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Departments</x-page-title>
            <p class="mt-1 text-sm text-slate-500">A department's kind decides which areas are ticked when you give someone that department on the Users page.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.departments.store') }}" class="grid gap-3 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-4">
        @csrf
        <div>
            <x-input-label for="department_name" value="Department" />
            <x-text-input id="department_name" name="name" class="mt-1 block w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="department_code" value="Code" />
            <x-text-input id="department_code" name="code" class="mt-1 block w-full" :value="old('code')" required />
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="department_kind" value="Kind" />
            <select id="department_kind" name="kind" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none" required>
                @foreach ($kinds as $kind => $label)
                    <option value="{{ $kind }}" @selected(old('kind') === $kind)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('kind')" class="mt-1" />
        </div>
        <div class="self-end"><x-primary-button>Add department</x-primary-button></div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Department</th>
                    <th class="px-5 py-3 font-medium">Kind</th>
                    <th class="px-5 py-3 font-medium">Staff</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($departments as $department)
                    <tr>
                        <td class="px-5 py-3">
                            <span class="block font-medium">{{ $department->name }}</span>
                            <span class="block text-slate-500">{{ $department->code }}</span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $kinds[$department->kind] ?? $department->kind }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $department->users_count }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $department->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
