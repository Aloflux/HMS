<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Users</x-page-title>
            <p class="mt-1 text-sm text-slate-500">The administrator has every screen. Everyone else gets only the areas you tick, or the areas that come with their department.</p>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route('admin.users.store') }}"
        class="rounded-lg bg-white p-5 shadow-sm"
        x-data="{
            account: 'staff',
            areas: {},
            departmentAreas: @js($departmentAreas),
            applyDepartment(id) {
                (this.departmentAreas[id] || []).forEach((key) => { this.areas[key] = true; });
            },
        }"
    >
        @csrf
        <h2 class="font-semibold">New user</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
            </div>
            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" />
            </div>
        </div>

        <fieldset class="mt-4">
            <legend class="text-sm font-medium text-slate-700">Access</legend>
            <div class="mt-2 flex flex-wrap gap-2">
                <label class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm" :class="account === 'administrator' ? 'bg-brand-50 font-semibold text-brand-800' : 'bg-slate-50 text-slate-700'">
                    <input type="radio" name="account" value="administrator" class="text-brand-700" x-model="account">
                    Administrator, full access
                </label>
                <label class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm" :class="account === 'staff' ? 'bg-brand-50 font-semibold text-brand-800' : 'bg-slate-50 text-slate-700'">
                    <input type="radio" name="account" value="staff" class="text-brand-700" x-model="account">
                    Chosen areas
                </label>
            </div>
        </fieldset>

        <div class="mt-4" x-show="account === 'staff'" x-cloak>
            <x-input-label for="department_id" value="Department" />
            <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none sm:max-w-sm" @change="applyDepartment($event.target.value)">
                <option value="">No department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-slate-500">Choosing a department ticks the areas that department uses. You can change the ticks.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($areas as $area)
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <input type="checkbox" name="areas[]" value="{{ $area['key'] }}" class="mt-1 rounded border-slate-300 text-brand-700" x-model="areas['{{ $area['key'] }}']">
                        <span>
                            <span class="block font-medium text-slate-900">{{ $area['label'] }}</span>
                            <span class="block text-slate-500">{{ $area['detail'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="mt-4"><x-primary-button>Create user</x-primary-button></div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Person</th>
                    <th class="px-5 py-3 font-medium">Access</th>
                    <th class="px-5 py-3 font-medium">Change</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-5 py-3 align-top">
                            <span class="block font-medium">{{ $user->name }}</span>
                            <span class="block text-slate-500">{{ $user->email }}</span>
                        </td>
                        <td class="px-5 py-3 align-top text-slate-600">{{ $user->accessSummary() }}</td>
                        <td class="px-5 py-3 align-top">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-2" x-data="{ account: @js($user->role === \App\Enums\Role::Administrator ? 'administrator' : 'staff') }">
                                @csrf
                                @method('PATCH')
                                <select name="account" x-model="account" class="rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                                    <option value="administrator">Administrator</option>
                                    <option value="staff">Chosen areas</option>
                                </select>
                                <select name="department_id" class="rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                                    <option value="">No department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected($user->department_id === $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div class="grid gap-1" x-show="account === 'staff'" x-cloak>
                                    @foreach ($areas as $area)
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="areas[]" value="{{ $area['key'] }}" class="rounded border-slate-300 text-brand-700" @checked(in_array($area['key'], $user->selectedAreaKeys(), true))>
                                            {{ $area['label'] }}
                                        </label>
                                    @endforeach
                                </div>
                                <select name="is_active" class="rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none">
                                    <option value="1" @selected($user->is_active)>Active</option>
                                    <option value="0" @selected(! $user->is_active)>Inactive</option>
                                </select>
                                <button class="text-start text-sm font-semibold text-brand-700">Save access</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.departments.store') }}" class="mt-4 grid gap-3 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-4">
        @csrf
        <div>
            <x-input-label for="department_name" value="Department" />
            <x-text-input id="department_name" name="name" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="department_code" value="Code" />
            <x-text-input id="department_code" name="code" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="department_kind" value="Kind" />
            <select id="department_kind" name="kind" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none" required>
                @foreach (['opd' => 'Outpatient', 'emergency' => 'Emergency', 'laboratory' => 'Laboratory', 'radiology' => 'Imaging', 'pharmacy' => 'Pharmacy', 'ward' => 'Ward', 'maternity' => 'Maternity', 'theatre' => 'Theatre', 'stores' => 'Stores', 'cashier' => 'Cashier', 'administration' => 'Administration'] as $kind => $label)
                    <option value="{{ $kind }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="self-end"><x-primary-button>Add department</x-primary-button></div>
    </form>

    <p class="mt-4 text-sm"><a class="text-brand-700" href="{{ route('admin.catalogue.index') }}">Price list and catalogues</a> · <a class="text-brand-700" href="{{ route('admin.audit.index') }}">Audit trail</a></p>
</x-app-layout>
