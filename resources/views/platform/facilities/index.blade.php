<x-app-layout>
    @php
        $fieldClass = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none';
        $initials = fn (string $name): string => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
        $tabs = ['all' => 'All', 'active' => 'Live', 'setup' => 'Setting up', 'inactive' => 'Inactive'];
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('platform.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-800">
                    <x-icon name="bx-left-arrow-alt" class="text-base" />
                    Platform overview
                </a>
                <x-page-title class="mt-1">Facilities</x-page-title>
                <p class="mt-1 text-sm text-slate-500">Provision facilities, switch them on or off, and manage who administers each one.</p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 rounded-md bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-brand hover:bg-brand-800" x-data @click="$dispatch('open-modal', 'provision-facility')">
                <x-icon name="bx-plus" class="text-lg" />
                Provision facility
            </button>
        </div>
    </x-slot>

    <section class="overflow-hidden rounded-lg bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5">
            <nav class="inline-flex flex-wrap gap-1 rounded-md bg-slate-100 p-1" aria-label="Filter by status">
                @foreach ($tabs as $value => $label)
                    <a href="{{ route('platform.facilities.index', array_filter(['q' => $search ?: null, 'status' => $value === 'all' ? null : $value, 'sort' => $sort === 'name' ? null : $sort])) }}" @class([
                        'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-semibold transition',
                        'bg-white text-slate-900 shadow-card' => $status === $value,
                        'text-slate-500 hover:text-slate-800' => $status !== $value,
                    ])>
                        {{ $label }}
                        <span class="rounded-full bg-slate-100 px-1.5 text-[11px] font-semibold text-slate-500">{{ $counts[$value] }}</span>
                    </a>
                @endforeach
            </nav>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                @if ($status !== 'all')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <label class="relative block">
                    <span class="sr-only">Search facilities</span>
                    <x-icon name="bx-search" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-slate-400" />
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name, code or district" class="h-10 w-64 rounded-md border-transparent bg-slate-100/70 ps-10 pe-3 text-sm focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none">
                </label>
                <label for="sort" class="sr-only">Sort</label>
                <select id="sort" name="sort" class="h-10 rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0" onchange="this.form.submit()">
                    <option value="name" @selected($sort === 'name')>Name A–Z</option>
                    <option value="newest" @selected($sort === 'newest')>Newest first</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-left text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Facility</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 font-semibold">Administrators</th>
                        <th class="px-5 py-3 text-right font-semibold">Staff</th>
                        <th class="px-5 py-3 text-right font-semibold">Patients</th>
                        <th class="px-5 py-3 font-semibold">Joined</th>
                        <th class="px-5 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($facilities as $facility)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-xs font-bold',
                                        'bg-brand-50 text-brand-800' => $facility->is_active,
                                        'bg-slate-100 text-slate-400' => ! $facility->is_active,
                                    ])>{{ $initials($facility->name) }}</span>
                                    <span class="min-w-0">
                                        <span class="block font-semibold text-slate-900">{{ $facility->name }}</span>
                                        <span class="block text-xs text-slate-400">{{ $facility->code }} · {{ $kinds[$facility->kind] ?? $facility->kind }} · {{ $facility->district }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                @if (! $facility->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Inactive</span>
                                @elseif (! $facility->isOnboarded())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Setting up</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand-800"><span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>Live</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-700 hover:text-brand-800">
                                    {{ $facility->admins_count }} {{ Str::plural('administrator', $facility->admins_count) }}
                                </a>
                                @if ($facility->active_admins_count === 0)
                                    <span class="mt-0.5 flex items-center gap-1 text-xs font-medium text-red-700"><x-icon name="bx-error-circle" class="text-sm" />None active</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-slate-600">{{ number_format($facility->staff_count) }}</td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-slate-600">{{ number_format($facility->patients_count) }}</td>
                            <td class="px-5 py-3.5 text-slate-600">
                                <span class="block">{{ $facility->created_at?->format('d M Y') }}</span>
                                <span class="block text-xs text-slate-400">{{ $facility->created_at?->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                        <x-icon name="bx-user-check" class="text-base" />
                                        Admins
                                    </a>
                                    <form method="POST" action="{{ route('platform.facilities.update', $facility) }}" @if ($facility->is_active) onsubmit="return confirm('Deactivate {{ addslashes($facility->name) }}? Its staff will be signed out and cannot sign in.')" @endif>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $facility->is_active ? 0 : 1 }}">
                                        @if ($facility->is_active)
                                            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md px-3 text-xs font-semibold text-slate-500 hover:bg-red-50 hover:text-red-700">
                                                <x-icon name="bx-power-off" class="text-base" />
                                                Deactivate
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-brand-700 px-3 text-xs font-semibold text-white shadow-brand hover:bg-brand-800">
                                                <x-icon name="bx-power-off" class="text-base" />
                                                Activate
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                {{ $search !== '' || $status !== 'all' ? 'No facilities match these filters.' : 'No facilities yet. Provision the first one.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <x-modal name="provision-facility" :show="$openProvision" maxWidth="2xl" focusable>
        <form method="POST" action="{{ route('platform.facilities.store') }}" class="p-6">
            @csrf
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-buildings" class="text-xl" /></span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Provision a facility</h2>
                        <p class="text-sm text-slate-500">Creates departments, stores, two wards, starter prices and a first administrator. It goes live straight away.</p>
                    </div>
                </div>
                <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" x-on:click="$dispatch('close')" aria-label="Close">
                    <x-icon name="bx-x" class="text-xl" />
                </button>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="facility-name" value="Facility name" />
                    <x-text-input id="facility-name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="facility-code" value="Short code" />
                    <x-text-input id="facility-code" name="code" class="mt-1 block w-full uppercase" :value="old('code')" placeholder="Used in patient numbers" maxlength="12" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="facility-kind" value="Type" />
                    <select id="facility-kind" name="kind" class="{{ $fieldClass }}">
                        @foreach ($kinds as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', 'hospital') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('kind')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="facility-district" value="District" />
                    <x-text-input id="facility-district" name="district" class="mt-1 block w-full" :value="old('district')" required />
                    <x-input-error :messages="$errors->get('district')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="facility-beds" value="Planned beds" />
                    <x-text-input id="facility-beds" name="bed_capacity" type="number" min="1" max="500" class="mt-1 block w-full" :value="old('bed_capacity')" required />
                    <x-input-error :messages="$errors->get('bed_capacity')" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 rounded-md bg-slate-50 p-4">
                <h3 class="text-sm font-bold text-slate-900">First administrator</h3>
                <p class="text-xs text-slate-500">They sign in with this temporary password and add the rest of the staff.</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="admin-name" value="Name" />
                        <x-text-input id="admin-name" name="admin_name" class="mt-1 block w-full" :value="old('admin_name')" required />
                        <x-input-error :messages="$errors->get('admin_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="admin-email" value="Email" />
                        <x-text-input id="admin-email" name="admin_email" type="email" class="mt-1 block w-full" :value="old('admin_email')" required />
                        <x-input-error :messages="$errors->get('admin_email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="admin-password" value="Temporary password" />
                        <x-text-input id="admin-password" name="admin_password" type="password" autocomplete="new-password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('admin_password')" class="mt-1" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>
                    <x-icon name="bx-plus" class="text-lg" />
                    Provision facility
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
