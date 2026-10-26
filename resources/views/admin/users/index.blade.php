<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-page-title>Users</x-page-title>
                <p class="mt-1 text-sm text-slate-500">Everyone who can sign in to {{ $currentFacility->name ?? 'this facility' }}.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 rounded-md bg-brand-700 px-4 py-2 text-sm font-semibold text-white shadow-brand hover:bg-brand-800">
                <x-icon name="bx-plus" class="text-lg" />
                Add user
            </a>
        </div>
    </x-slot>

    <div class="rounded-2xl bg-white shadow-card">
        <form method="GET" action="{{ route('admin.users.index') }}" class="border-b border-slate-100 p-4">
            <label for="q" class="sr-only">Search users</label>
            <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Search by name or email" class="w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm shadow-none focus:outline-none sm:max-w-sm">
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 font-medium">Person</th>
                        <th class="px-5 py-3 font-medium">Access</th>
                        <th class="px-5 py-3 font-medium">Department</th>
                        <th class="px-5 py-3 font-medium">Last sign-in</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.users.edit', $user) }}" class="block">
                                    <span class="block font-medium text-slate-900">{{ $user->name }}</span>
                                    <span class="block text-xs text-slate-400">{{ $user->email }}</span>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->accessAreas() }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->department?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium uppercase tracking-wide {{ $user->is_active ? 'bg-brand-50 text-brand-800' : 'bg-slate-100 text-slate-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-3 text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-slate-400 hover:text-brand-700" aria-label="Edit {{ $user->name }}">
                                    <x-icon name="bx-edit" class="text-xl" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-slate-500">No users match that search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
