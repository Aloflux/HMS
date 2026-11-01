<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-700">Users</a>
            <x-page-title>Add user</x-page-title>
            <p class="mt-1 text-sm text-slate-500">We email them an invitation. They choose their own password and appear as active once they accept. The administrator has every screen; everyone else gets only the areas you tick, or the areas that come with their department.</p>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route('admin.users.store') }}"
        class="rounded-lg bg-white p-5 shadow-card"
        x-data="{
            account: @js(old('account', 'staff')),
            areas: @js((object) array_fill_keys(old('areas', []), true)),
            departmentAreas: @js($departmentAreas),
            applyDepartment(id) {
                (this.departmentAreas[id] || []).forEach((key) => { this.areas[key] = true; });
            },
        }"
    >
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
        </div>

        <div class="mt-6">
            @include('admin.users.partials.access', ['areas' => $areas, 'departments' => $departments])
        </div>

        <div class="mt-6 flex items-center gap-4">
            <x-primary-button>Send invitation</x-primary-button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600">Cancel</a>
        </div>
    </form>
</x-app-layout>
