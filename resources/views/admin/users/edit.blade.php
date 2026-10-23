<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-700">Users</a>
            <x-page-title>{{ $user->name }}</x-page-title>
            <p class="mt-1 text-sm text-slate-500">Last signed in {{ $user->last_login_at?->diffForHumans() ?? 'never' }}.</p>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route('admin.users.update', $user) }}"
        class="rounded-lg bg-white p-5 shadow-sm"
        x-data="{
            account: @js(old('account', $user->role === \App\Enums\Role::Administrator ? 'administrator' : 'staff')),
            areas: @js((object) array_fill_keys(old('areas', $user->selectedAreaKeys()), true)),
            departmentAreas: @js($departmentAreas),
            applyDepartment(id) {
                (this.departmentAreas[id] || []).forEach((key) => { this.areas[key] = true; });
            },
        }"
    >
        @csrf
        @method('PATCH')
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $user->phone)" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
        </div>

        <div class="mt-6">
            @include('admin.users.partials.access', ['areas' => $areas, 'departments' => $departments, 'selectedDepartment' => $user->department_id])
        </div>

        <fieldset class="mt-6">
            <legend class="text-sm font-medium text-slate-700">Status</legend>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach (['1' => 'Active, can sign in', '0' => 'Inactive, cannot sign in'] as $value => $label)
                    <label class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="radio" name="is_active" value="{{ $value }}" class="text-brand-700" @checked((string) old('is_active', (int) $user->is_active) === (string) $value)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-6 flex items-center gap-4">
            <x-primary-button>Save changes</x-primary-button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600">Cancel</a>
        </div>
    </form>
</x-app-layout>
