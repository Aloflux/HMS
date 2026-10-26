<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('platform.facilities.index') }}" class="text-sm text-brand-700">Facilities</a>
            <x-page-title>{{ $facility->name }} administrators</x-page-title>
            <p class="mt-1 text-sm text-slate-500">Administrators run the facility: its staff, prices, and approvals. They add the other {{ $staffCount }} {{ Str::plural('user', $staffCount) }} themselves. You never see patient records from here.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('platform.facilities.admins.store', $facility) }}" class="rounded-lg bg-white p-5 shadow-card">
        @csrf
        <h2 class="font-bold text-slate-900">Add an administrator</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
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
            <div>
                <x-input-label for="password" value="Temporary password" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
        </div>
        <div class="mt-4"><x-primary-button>Add administrator</x-primary-button></div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-card">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 font-medium">Administrator</th>
                    <th class="px-5 py-3 font-medium">Last sign-in</th>
                    <th class="px-5 py-3 font-medium">Change</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($admins as $admin)
                    <tr>
                        <td class="px-5 py-3 align-top">
                            <span class="block font-medium">{{ $admin->name }}</span>
                            <span class="block text-slate-500">{{ $admin->email }}</span>
                        </td>
                        <td class="px-5 py-3 align-top text-slate-600">{{ $admin->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-5 py-3 align-top">
                            <form method="POST" action="{{ route('platform.facilities.admins.update', [$facility, $admin]) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="is_active" class="rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm shadow-none focus:outline-none">
                                    <option value="1" @selected($admin->is_active)>Active</option>
                                    <option value="0" @selected(! $admin->is_active)>Inactive</option>
                                </select>
                                <x-text-input name="password" type="password" placeholder="New password (optional)" autocomplete="new-password" aria-label="New password for {{ $admin->name }}" class="w-56 text-sm" />
                                <button class="text-sm font-semibold text-brand-700">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-6 text-center text-slate-500">This facility has no administrator. Add one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
