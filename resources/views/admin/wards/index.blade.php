<x-app-layout>
    @php
        $allBeds = $wards->flatMap->beds;
        $openBeds = $allBeds->where('status', '!=', 'closed')->count();
        $closedBeds = $allBeds->where('status', 'closed')->count();
        $fieldClass = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none';
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-page-title>Wards & beds</x-page-title>
                <p class="mt-1 text-sm text-slate-500">Add wards, grow or shrink them, and take beds out of service. Beds with admission history can be closed but never removed.</p>
            </div>
            <a href="{{ route('wards.index') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card hover:shadow-card-hover">
                <x-icon name="bx-bed" class="text-lg text-brand-700" />
                Open ward board
            </a>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-card">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Wards</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ $wards->count() }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Beds in service</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ $openBeds }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-card">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Closed beds</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight {{ $closedBeds > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $closedBeds }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.wards.store') }}" class="mt-6 rounded-lg bg-white p-5 shadow-card">
        @csrf
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                <x-icon name="bx-plus" class="text-xl" />
            </span>
            <div>
                <h2 class="font-bold text-slate-900">Add a ward</h2>
                <p class="text-sm text-slate-500">Beds are numbered from the ward code, for example PW-1, PW-2.</p>
            </div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <x-input-label for="ward_name" value="Ward name" />
                <x-text-input id="ward_name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="Paediatric ward" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="ward_code" value="Code" />
                <x-text-input id="ward_code" name="code" class="mt-1 block w-full uppercase" :value="old('code')" placeholder="PW" maxlength="12" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="ward_department" value="Department" />
                <select id="ward_department" name="department_id" class="{{ $fieldClass }}">
                    <option value="">None</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="ward_beds" value="Starting beds" />
                <x-text-input id="ward_beds" name="beds" type="number" min="0" max="200" class="mt-1 block w-full" :value="old('beds', 4)" required />
                <x-input-error :messages="$errors->get('beds')" class="mt-1" />
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="hidden" name="create_store" value="0">
                <input type="checkbox" name="create_store" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500" @checked(old('create_store', true))>
                Also create a ward stock store
            </label>
            <x-primary-button>
                <x-icon name="bx-plus" class="text-lg" />
                Add ward
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @forelse ($wards as $ward)
            @php
                $inService = $ward->beds->where('status', '!=', 'closed');
                $free = $ward->beds->where('status', 'available')->count();
                $hasHistory = $ward->beds->sum('admissions_count') > 0 || $ward->beds->contains('status', 'occupied');
            @endphp
            <section x-data="{ editing: false }" class="rounded-lg bg-white shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900">{{ $ward->name }}</h2>
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $ward->code }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $ward->department?->name ?? 'No department' }} · {{ $free }} free of {{ $inService->count() }} in service
                            @if ($ward->beds->count() > $inService->count())
                                · {{ $ward->beds->count() - $inService->count() }} closed
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-slate-100 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-200" @click="editing = !editing">
                            <x-icon name="bx-edit" class="text-base" />
                            Edit
                        </button>
                        @unless ($hasHistory)
                            <form method="POST" action="{{ route('admin.wards.destroy', $ward) }}" onsubmit="return confirm('Remove {{ $ward->name }} and all its beds?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-700" aria-label="Remove {{ $ward->name }}">
                                    <x-icon name="bx-trash" class="text-lg" />
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>

                <form x-show="editing" x-cloak method="POST" action="{{ route('admin.wards.update', $ward) }}" class="mx-5 mb-5 grid gap-3 rounded-md bg-slate-50 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="ward-{{ $ward->id }}-name" value="Ward name" />
                        <x-text-input id="ward-{{ $ward->id }}-name" name="name" class="mt-1 block w-full" :value="$ward->name" required />
                    </div>
                    <div>
                        <x-input-label for="ward-{{ $ward->id }}-department" value="Department" />
                        <select id="ward-{{ $ward->id }}-department" name="department_id" class="{{ $fieldClass }}">
                            <option value="">None</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) $ward->department_id === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>Save</x-primary-button>
                </form>

                <div class="px-5">
                    <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @forelse ($ward->beds as $bed)
                            <li class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm {{ $bed->status === 'closed' ? 'bg-slate-100 text-slate-400' : 'bg-slate-50' }}">
                                <span class="min-w-0">
                                    <span class="block font-semibold {{ $bed->status === 'closed' ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $bed->code }}</span>
                                    <x-status :value="$bed->status === 'available' ? 'free' : $bed->status" class="mt-1" />
                                </span>
                                <span class="flex shrink-0 items-center">
                                    @if ($bed->status === 'closed')
                                        <form method="POST" action="{{ route('admin.beds.update', $bed) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="reopen">
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-brand-50 hover:text-brand-700" aria-label="Reopen {{ $bed->code }}" title="Reopen">
                                                <x-icon name="bx-revision" class="text-lg" />
                                            </button>
                                        </form>
                                    @elseif ($bed->status !== 'occupied')
                                        <form method="POST" action="{{ route('admin.beds.update', $bed) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="close">
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-amber-50 hover:text-amber-700" aria-label="Close {{ $bed->code }}" title="Close">
                                                <x-icon name="bx-block" class="text-lg" />
                                            </button>
                                        </form>
                                    @endif
                                    @if ($bed->status !== 'occupied' && $bed->admissions_count === 0)
                                        <form method="POST" action="{{ route('admin.beds.destroy', $bed) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-700" aria-label="Remove {{ $bed->code }}" title="Remove">
                                                <x-icon name="bx-trash" class="text-lg" />
                                            </button>
                                        </form>
                                    @endif
                                </span>
                            </li>
                        @empty
                            <li class="col-span-full rounded-md bg-slate-50 px-3 py-4 text-sm text-slate-500">No beds yet. Add some below.</li>
                        @endforelse
                    </ul>
                </div>

                <form method="POST" action="{{ route('admin.wards.beds.store', $ward) }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-b-lg bg-slate-50/80 px-5 py-4">
                    @csrf
                    <div>
                        <x-input-label for="ward-{{ $ward->id }}-count" value="Add beds" />
                        <x-text-input id="ward-{{ $ward->id }}-count" name="count" type="number" min="1" max="100" value="1" class="mt-1 block w-24" required />
                    </div>
                    <x-secondary-button type="submit">
                        <x-icon name="bx-plus" class="text-lg" />
                        Add to {{ $ward->code }}
                    </x-secondary-button>
                </form>
            </section>
        @empty
            <div class="rounded-lg bg-white px-5 py-12 text-center text-sm text-slate-500 shadow-card xl:col-span-2">No wards yet. Add the first one above.</div>
        @endforelse
    </div>
</x-app-layout>
