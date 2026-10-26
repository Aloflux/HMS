<x-guest-layout plain>
    @php
        $labels = ['facility' => 'Facility details', 'wards' => 'Wards & beds', 'finish' => 'Go live'];
        $currentIndex = array_search($step, $steps, true);
        $fieldClass = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none';
    @endphp

    <header class="bg-white shadow-card">
        <div class="mx-auto flex h-16 max-w-4xl items-center justify-between px-4 sm:px-6">
            <span class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-brand-700 text-white shadow-brand">
                    <x-icon name="bx-plus-medical" class="text-lg" />
                </span>
                <span class="text-lg font-bold tracking-tight text-slate-900">Tunzuri</span>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                    <x-icon name="bx-log-out" class="text-lg" />
                    Finish later
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-brand-700">Setup · step {{ $currentIndex + 1 }} of {{ count($steps) }}</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Welcome, {{ strtok(auth()->user()->name, ' ') }}. Let's get {{ $facility->name }} ready.</h1>

        <ol class="mt-6 grid grid-cols-3 gap-2">
            @foreach ($steps as $index => $key)
                @php($done = $index < $currentIndex)
                @php($active = $index === $currentIndex)
                <li>
                    <a href="{{ $done ? route('onboarding.show', $key) : '#' }}" @class([
                        'flex items-center gap-2 rounded-md px-3 py-2.5 text-sm font-semibold transition',
                        'bg-brand-700 text-white shadow-brand' => $active,
                        'bg-white text-brand-800 shadow-card hover:shadow-card-hover' => $done,
                        'pointer-events-none bg-white/60 text-slate-400' => ! $active && ! $done,
                    ])>
                        <span @class([
                            'inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs',
                            'bg-white/20' => $active,
                            'bg-brand-50' => $done,
                            'bg-slate-100' => ! $active && ! $done,
                        ])>
                            @if ($done)
                                <x-icon name="bx-check" class="text-base" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span class="truncate">{{ $labels[$key] }}</span>
                    </a>
                </li>
            @endforeach
        </ol>

        @if ($errors->any())
            <div class="mt-6 flex items-start gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-card">
                <x-icon name="bx-error-circle" class="mt-0.5 text-lg" />
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($step === 'facility')
            <form method="POST" action="{{ route('onboarding.facility') }}" class="mt-6 rounded-lg bg-white p-6 shadow-card">
                @csrf
                <h2 class="text-lg font-bold text-slate-900">Facility details</h2>
                <p class="text-sm text-slate-500">These appear on receipts, reports and patient documents.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Facility name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $facility->name)" required />
                    </div>
                    <div>
                        <x-input-label for="kind" value="Type" />
                        <select id="kind" name="kind" class="{{ $fieldClass }}" required>
                            @foreach ($kinds as $value => $label)
                                <option value="{{ $value }}" @selected(old('kind', $facility->kind) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="district" value="District" />
                        <x-text-input id="district" name="district" class="mt-1 block w-full" :value="old('district', $facility->district)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Physical address" />
                        <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $facility->address)" placeholder="Plot 12, Hospital Road" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Facility phone" />
                        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $facility->phone)" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Facility email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $facility->email)" />
                    </div>
                    <div>
                        <x-input-label for="moh_code" value="MoH facility code (optional)" />
                        <x-text-input id="moh_code" name="moh_code" class="mt-1 block w-full" :value="old('moh_code', $facility->moh_code)" />
                    </div>
                    <div>
                        <x-input-label value="Tunzuri code" />
                        <p class="mt-1 flex h-[42px] items-center rounded-md bg-slate-50 px-3 text-sm font-semibold text-slate-700">{{ $facility->code }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <x-primary-button>
                        Save and continue
                        <x-icon name="bx-right-arrow-alt" class="text-lg" />
                    </x-primary-button>
                </div>
            </form>
        @elseif ($step === 'wards')
            <form method="POST" action="{{ route('onboarding.wards') }}" x-data="{ rows: [] }" class="mt-6 rounded-lg bg-white p-6 shadow-card">
                @csrf
                <h2 class="text-lg font-bold text-slate-900">Wards & beds</h2>
                <p class="text-sm text-slate-500">We created two wards to start. Rename them, change how many beds each has, or add more. You can change all of this later under Administration.</p>

                <div class="mt-5 grid gap-3">
                    @foreach ($wards as $ward)
                        <div class="grid gap-3 rounded-md bg-slate-50 p-4 sm:grid-cols-[minmax(0,1fr)_8rem] sm:items-end">
                            <div>
                                <x-input-label for="ward-{{ $ward->id }}-name" value="Ward name" />
                                <x-text-input id="ward-{{ $ward->id }}-name" name="wards[{{ $ward->id }}][name]" class="mt-1 block w-full" :value="old('wards.'.$ward->id.'.name', $ward->name)" required />
                            </div>
                            <div>
                                <x-input-label for="ward-{{ $ward->id }}-beds" value="Beds" />
                                <x-text-input id="ward-{{ $ward->id }}-beds" name="wards[{{ $ward->id }}][beds]" type="number" min="0" max="200" class="mt-1 block w-full" :value="old('wards.'.$ward->id.'.beds', $ward->beds_count)" required />
                            </div>
                        </div>
                    @endforeach

                    <template x-for="(row, index) in rows" :key="row.id">
                        <div class="grid gap-3 rounded-md bg-brand-50/60 p-4 sm:grid-cols-[minmax(0,1fr)_8rem_auto] sm:items-end">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" :for="'new-ward-' + row.id + '-name'">New ward name</label>
                                <input type="text" :id="'new-ward-' + row.id + '-name'" :name="'new_wards[' + index + '][name]'" placeholder="Children's ward" class="{{ $fieldClass }}" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" :for="'new-ward-' + row.id + '-beds'">Beds</label>
                                <input type="number" min="0" max="200" value="4" :id="'new-ward-' + row.id + '-beds'" :name="'new_wards[' + index + '][beds]'" class="{{ $fieldClass }}" required>
                            </div>
                            <button type="button" class="inline-flex h-[42px] w-[42px] items-center justify-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-700" @click="rows.splice(index, 1)" aria-label="Remove this ward">
                                <x-icon name="bx-trash" class="text-lg" />
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" class="mt-3 inline-flex items-center gap-2 rounded-md bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200" @click="rows.push({ id: Date.now() })">
                    <x-icon name="bx-plus" class="text-lg" />
                    Add another ward
                </button>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <a href="{{ route('onboarding.show', 'facility') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">
                        <x-icon name="bx-left-arrow-alt" class="text-lg" />
                        Back
                    </a>
                    <x-primary-button>
                        Save and continue
                        <x-icon name="bx-right-arrow-alt" class="text-lg" />
                    </x-primary-button>
                </div>
            </form>
        @else
            @php($bedTotal = $wards->sum('beds_count'))
            <div class="mt-6 grid gap-6 md:grid-cols-[minmax(0,1fr)_18rem]">
                <section class="rounded-lg bg-white p-6 shadow-card">
                    <h2 class="text-lg font-bold text-slate-900">Ready to go live</h2>
                    <p class="text-sm text-slate-500">Here is what your facility starts with.</p>

                    <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-md bg-slate-50 p-4">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Facility</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $facility->name }}</dd>
                            <dd class="text-sm text-slate-500">{{ $kinds[$facility->kind] ?? $facility->kind }} · {{ $facility->district }}</dd>
                        </div>
                        <div class="rounded-md bg-slate-50 p-4">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Wards & beds</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $wards->count() }} {{ str('ward')->plural($wards->count()) }} · {{ $bedTotal }} {{ str('bed')->plural($bedTotal) }}</dd>
                            <dd class="text-sm text-slate-500">{{ $wards->pluck('name')->join(', ') }}</dd>
                        </div>
                    </dl>

                    <ul class="mt-5 space-y-2 text-sm text-slate-600">
                        @foreach (['Standard departments: outpatient, emergency, lab, imaging, pharmacy, maternity, theatre and more', 'Stock stores for the main store, pharmacy, lab, theatre and each ward', 'Starter prices for registration, consultation and bed days'] as $item)
                            <li class="flex items-start gap-2">
                                <x-icon name="bx-check-circle" class="mt-0.5 text-lg text-brand-600" />
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 flex items-center justify-between gap-3">
                        <a href="{{ route('onboarding.show', 'wards') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-600 hover:text-slate-900">
                            <x-icon name="bx-left-arrow-alt" class="text-lg" />
                            Back
                        </a>
                        <form method="POST" action="{{ route('onboarding.finish') }}">
                            @csrf
                            <x-primary-button>
                                <x-icon name="bx-rocket" class="text-lg" />
                                Go live
                            </x-primary-button>
                        </form>
                    </div>
                </section>

                <aside class="rounded-lg bg-brand-900 p-6 text-white shadow-card">
                    <h3 class="font-bold">After you go live</h3>
                    <ol class="mt-3 space-y-3 text-sm text-brand-100">
                        <li><span class="font-semibold text-white">Add your staff</span> on the Users page and tick the areas each person works in.</li>
                        <li><span class="font-semibold text-white">Review the price list</span> so charges match your facility.</li>
                        <li><span class="font-semibold text-white">Register your first patient</span> from the top bar.</li>
                    </ol>
                    <p class="mt-4 text-xs text-brand-200">We will email you this list too.</p>
                </aside>
            </div>
        @endif
    </main>
</x-guest-layout>
