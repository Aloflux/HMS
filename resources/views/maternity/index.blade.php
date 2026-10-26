<x-app-layout>
    <x-slot name="header"><x-page-title>Maternity</x-page-title></x-slot>

    <datalist id="mothers">
        @foreach ($patients as $patient)
            <option value="{{ $patient->patient_number }}">{{ $patient->fullName() }}</option>
        @endforeach
    </datalist>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('maternity.antenatal') }}" class="rounded-2xl bg-white p-5 shadow-card">
            @csrf
            <h2 class="font-bold text-slate-900">Antenatal visit</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="anc-patient" value="Mother's patient number" />
                    <x-text-input id="anc-patient" name="patient_number" list="mothers" class="mt-1 block w-full" :value="old('patient_number')" placeholder="Type a number or name" required />
                </div>
                <div>
                    <x-input-label for="gestation" value="Gestation (weeks)" />
                    <x-text-input id="gestation" name="gestation_weeks" type="number" min="1" max="45" class="mt-1 block w-full" :value="old('gestation_weeks')" required />
                </div>
                <div>
                    <x-input-label for="fundal" value="Fundal height (cm)" />
                    <x-text-input id="fundal" name="fundal_height_cm" type="number" min="1" max="50" class="mt-1 block w-full" :value="old('fundal_height_cm')" />
                </div>
                <div>
                    <x-input-label for="fetal-heart" value="Fetal heart rate" />
                    <x-text-input id="fetal-heart" name="fetal_heart" class="mt-1 block w-full" :value="old('fetal_heart')" placeholder="140 bpm" />
                </div>
                <div>
                    <x-input-label for="anc-bp" value="Blood pressure" />
                    <x-text-input id="anc-bp" name="blood_pressure" class="mt-1 block w-full" :value="old('blood_pressure')" placeholder="120/80" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="anc-notes" value="Notes" />
                    <textarea id="anc-notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-4"><x-primary-button>Save antenatal visit</x-primary-button></div>
        </form>

        <form method="POST" action="{{ route('maternity.deliveries') }}" class="rounded-2xl bg-white p-5 shadow-card">
            @csrf
            <h2 class="font-bold text-slate-900">Delivery register</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="delivery-patient" value="Mother's patient number" />
                    <x-text-input id="delivery-patient" name="patient_number" list="mothers" class="mt-1 block w-full" :value="old('patient_number')" placeholder="Type a number or name" required />
                </div>
                <div>
                    <x-input-label for="delivered-at" value="Delivered at" />
                    <x-text-input id="delivered-at" name="delivered_at" type="datetime-local" class="mt-1 block w-full" :value="old('delivered_at')" required />
                </div>
                <div>
                    <x-input-label for="mode" value="Mode" />
                    <select id="mode" name="mode" class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        @foreach (['svd' => 'Normal vaginal delivery', 'caesarean' => 'Caesarean', 'assisted' => 'Assisted'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('mode') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="outcome" value="Outcome" />
                    <select id="outcome" name="outcome" class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        @foreach (['live' => 'Live birth', 'stillbirth' => 'Stillbirth'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('outcome') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="baby-sex" value="Baby's sex" />
                    <select id="baby-sex" name="baby_sex" class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        <option value="">Not recorded</option>
                        <option value="female" @selected(old('baby_sex') === 'female')>Female</option>
                        <option value="male" @selected(old('baby_sex') === 'male')>Male</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="birth-weight" value="Birth weight (kg)" />
                    <x-text-input id="birth-weight" name="birth_weight_kg" type="number" step="0.01" min="0.3" max="7" class="mt-1 block w-full" :value="old('birth_weight_kg')" />
                </div>
                <div>
                    <x-input-label for="apgar" value="Apgar score" />
                    <x-text-input id="apgar" name="apgar" type="number" min="0" max="10" class="mt-1 block w-full" :value="old('apgar')" />
                </div>
            </div>
            <div class="mt-4"><x-primary-button>Save delivery</x-primary-button></div>
        </form>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Recent antenatal visits</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($antenatal as $row)
                    <li class="flex justify-between gap-3 py-2">
                        <a href="{{ route('patients.show', $row->patient) }}" class="font-medium text-slate-900">{{ $row->patient->fullName() }}</a>
                        <span class="text-slate-500">{{ $row->gestation_weeks }} weeks · {{ $row->seen_at->format('d M Y') }}</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-500">No antenatal visits yet.</li>
                @endforelse
            </ul>
        </section>
        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Deliveries</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @forelse ($deliveries as $row)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <span>
                            <a href="{{ route('patients.show', $row->patient) }}" class="font-medium text-slate-900">{{ $row->patient->fullName() }}</a>
                            <span class="block text-xs text-slate-500">{{ $row->delivered_at?->format('d M Y H:i') }} · {{ ['svd' => 'Normal delivery', 'caesarean' => 'Caesarean', 'assisted' => 'Assisted'][$row->mode] ?? $row->mode }}</span>
                        </span>
                        <x-status :value="$row->outcome" />
                    </li>
                @empty
                    <li class="py-2 text-slate-500">No deliveries recorded yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
