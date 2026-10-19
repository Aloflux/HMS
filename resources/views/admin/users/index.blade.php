<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-group">Users and departments</x-page-title></x-slot>
    <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-3">
        @csrf
        <input name="name" required placeholder="Name" class="rounded-md border-slate-300 text-sm">
        <input name="email" type="email" required placeholder="Email" class="rounded-md border-slate-300 text-sm">
        <input name="password" type="password" required placeholder="Password" class="rounded-md border-slate-300 text-sm">
        <input name="phone" placeholder="Phone" class="rounded-md border-slate-300 text-sm">
        <select name="role" class="rounded-md border-slate-300 text-sm">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ $role->label() }}</option>
            @endforeach
        </select>
        <select name="department_id" class="rounded-md border-slate-300 text-sm">
            <option value="">Department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
        <div class="md:col-span-3"><x-primary-button>Create user</x-primary-button></div>
    </form>
    <form method="POST" action="{{ route('admin.departments.store') }}" class="mt-4 grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4">
        @csrf
        <input name="name" required placeholder="Department name" class="rounded-md border-slate-300 text-sm">
        <input name="code" required placeholder="Code" class="rounded-md border-slate-300 text-sm">
        <input name="kind" required placeholder="Kind" class="rounded-md border-slate-300 text-sm">
        <x-primary-button>Add department</x-primary-button>
    </form>
    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            @foreach ($users as $user)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3">{{ $user->name }}<br><span class="text-slate-500">{{ $user->email }}</span></td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex flex-wrap gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="role" class="rounded-md border-slate-300 text-sm">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($user->role->value === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <select name="is_active" class="rounded-md border-slate-300 text-sm">
                                <option value="1" @selected($user->is_active)>Active</option>
                                <option value="0" @selected(! $user->is_active)>Inactive</option>
                            </select>
                            <button class="text-brand-700">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <p class="mt-4 text-sm"><a class="text-brand-700" href="{{ route('admin.catalogue.index') }}">Price list and catalogues</a> · <a class="text-brand-700" href="{{ route('admin.audit.index') }}">Audit trail</a></p>
</x-app-layout>
