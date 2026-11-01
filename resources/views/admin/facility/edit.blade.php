<x-app-layout>
    @php($fieldClass = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none')

    <x-slot name="header">
        <div>
            <x-page-title>Facility settings</x-page-title>
            <p class="mt-1 text-sm text-slate-500">These details appear on receipts, reports and patient documents.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.facility.update') }}" class="grid gap-6">
        @csrf
        @method('PATCH')

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Collections</h2>
            <p class="text-sm text-slate-500">The currency your cashiers collect in. Prices, bills, receipts and reports are shown in it.</p>
            <div class="mt-4 max-w-sm">
                <x-input-label for="currency" value="Currency" />
                <x-search-select id="currency" name="currency" class="mt-1" :options="\App\Support\Money::currencyOptions()" :selected="old('currency', $facility->currency)" placeholder="Search currencies" required />
                <x-input-error :messages="$errors->get('currency')" class="mt-1" />
            </div>
            @if ($hasBills)
                <p class="mt-3 flex items-start gap-2 rounded-md bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                    <x-icon name="bx-info-circle" class="mt-0.5 text-base" />
                    Changing the currency does not convert prices. Update your price list to match. Bills already raised keep the currency they were raised in.
                </p>
            @endif
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Facility details</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Facility name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $facility->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="kind" value="Type" />
                    <select id="kind" name="kind" class="{{ $fieldClass }}" required>
                        @foreach ($kinds as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', $facility->kind) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('kind')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="district" value="District" />
                    <x-text-input id="district" name="district" class="mt-1 block w-full" :value="old('district', $facility->district)" required />
                    <x-input-error :messages="$errors->get('district')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="address" value="Physical address" />
                    <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $facility->address)" />
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone" value="Facility phone" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $facility->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Facility email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $facility->email)" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="moh_code" value="MoH facility code (optional)" />
                    <x-text-input id="moh_code" name="moh_code" class="mt-1 block w-full" :value="old('moh_code', $facility->moh_code)" />
                    <x-input-error :messages="$errors->get('moh_code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Tunzuri code" />
                    <p class="mt-1 flex h-[42px] items-center rounded-md bg-slate-50 px-3 text-sm font-semibold text-slate-700">{{ $facility->code }}</p>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <x-primary-button>Save settings</x-primary-button>
        </div>
    </form>
</x-app-layout>
