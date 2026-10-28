@php
    $select = 'mt-1 block w-full rounded-md border-slate-300 text-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Price list</x-page-title>
            <p class="mt-1 text-sm text-slate-500">Services, stock items, lab tests, and imaging studies. Lab tests and studies are charged at the price of the service they link to.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Services</h2>
            <form method="POST" action="{{ route('admin.services.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                @csrf
                <div><x-input-label for="service-code" value="Code" /><x-text-input id="service-code" name="code" class="mt-1 block w-full" required /></div>
                <div><x-input-label for="service-name" value="Name" /><x-text-input id="service-name" name="name" class="mt-1 block w-full" required /></div>
                <div><x-input-label for="service-category" value="Category" /><x-text-input id="service-category" name="category" class="mt-1 block w-full" placeholder="laboratory, imaging, procedure" required /></div>
                <div><x-input-label for="service-price" value="Cash price (UGX)" /><x-text-input id="service-price" name="cash_price" type="number" min="0" class="mt-1 block w-full" required /></div>
                <div class="sm:col-span-2"><x-primary-button>Add service</x-primary-button></div>
            </form>
            <ul class="mt-4 max-h-64 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 text-sm">
                @foreach ($services as $service)
                    <li class="flex justify-between gap-3 py-2"><span><span class="font-medium">{{ $service->code }}</span> {{ $service->name }}</span><span>{{ \App\Support\Money::format($service->cash_price) }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Stock items</h2>
            <form method="POST" action="{{ route('admin.items.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                @csrf
                <div><x-input-label for="item-sku" value="Code (optional)" /><x-text-input id="item-sku" name="sku" class="mt-1 block w-full" placeholder="Made from the name if blank" /></div>
                <div><x-input-label for="item-name" value="Name" /><x-text-input id="item-name" name="name" class="mt-1 block w-full" required /></div>
                <div>
                    <x-input-label for="item-category" value="Category" />
                    <select id="item-category" name="category" class="{{ $select }}"><option value="medicine">Medicine</option><option value="consumable">Consumable</option><option value="reagent">Reagent</option></select>
                </div>
                <div>
                    <x-input-label for="item-billing" value="Billing" />
                    <select id="item-billing" name="billing_type" class="{{ $select }}">@foreach ($billingTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
                </div>
                <div><x-input-label for="item-unit" value="Unit" /><x-text-input id="item-unit" name="unit" class="mt-1 block w-full" placeholder="tablet, vial, pair" required /></div>
                <div><x-input-label for="item-price" value="Cash price (UGX)" /><x-text-input id="item-price" name="cash_price" type="number" min="0" class="mt-1 block w-full" required /></div>
                <div><x-input-label for="item-reorder" value="Reorder when main store falls to" /><x-text-input id="item-reorder" name="reorder_level" type="number" min="0" value="0" class="mt-1 block w-full" required /></div>
                <div class="flex items-end"><x-primary-button>Add item</x-primary-button></div>
            </form>
            <ul class="mt-4 max-h-64 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 text-sm">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-3 py-2"><span><span class="font-medium">{{ $item->name }}</span> <span class="text-slate-500">{{ $item->unit }} · {{ $item->billing_type->label() }}</span></span><span>{{ \App\Support\Money::format($item->cash_price) }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Lab tests</h2>
            @if ($services->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Add the service that sets this test's price first.</p>
            @else
                <form method="POST" action="{{ route('admin.lab-tests.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div><x-input-label for="test-code" value="Code" /><x-text-input id="test-code" name="code" class="mt-1 block w-full" required /></div>
                    <div><x-input-label for="test-name" value="Name" /><x-text-input id="test-name" name="name" class="mt-1 block w-full" required /></div>
                    <div>
                        <x-input-label for="test-service" value="Priced as service" />
                        <x-search-select id="test-service" name="service_id" :options="$services->map(fn ($service) => ['value' => $service->id, 'label' => $service->name, 'hint' => \App\Support\Money::format($service->cash_price)])" placeholder="Search services" required />
                    </div>
                    <div><x-input-label for="test-specimen" value="Specimen" /><x-text-input id="test-specimen" name="specimen" class="mt-1 block w-full" placeholder="Blood, urine" /></div>
                    <div><x-input-label for="test-unit" value="Result unit" /><x-text-input id="test-unit" name="unit" class="mt-1 block w-full" placeholder="g/dL" /></div>
                    <div><x-input-label for="test-reference" value="Reference range" /><x-text-input id="test-reference" name="reference_text" class="mt-1 block w-full" placeholder="12–16" /></div>
                    <div>
                        <x-input-label for="test-reagent" value="Reagent used" />
                        <x-search-select id="test-reagent" name="reagent_item_id" :options="$items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->unit])" empty-label="None" selected="" placeholder="Search items" />
                    </div>
                    <div><x-input-label for="test-reagent-qty" value="Reagent per test" /><x-text-input id="test-reagent-qty" name="reagent_quantity" type="number" min="1" value="1" class="mt-1 block w-full" /></div>
                    <div class="sm:col-span-2"><x-primary-button>Add test</x-primary-button></div>
                </form>
            @endif
            <ul class="mt-4 max-h-64 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 text-sm">
                @foreach ($tests as $test)
                    <li class="flex justify-between gap-3 py-2"><span><span class="font-medium">{{ $test->name }}</span> <span class="text-slate-500">{{ $test->unit }} {{ $test->reference_text }}</span></span><span>{{ \App\Support\Money::format($test->service?->cash_price ?? 0) }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Imaging studies</h2>
            @if ($services->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Add the service that sets this study's price first.</p>
            @else
                <form method="POST" action="{{ route('admin.studies.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div><x-input-label for="study-code" value="Code" /><x-text-input id="study-code" name="code" class="mt-1 block w-full" required /></div>
                    <div><x-input-label for="study-name" value="Name" /><x-text-input id="study-name" name="name" class="mt-1 block w-full" required /></div>
                    <div class="sm:col-span-2">
                        <x-input-label for="study-service" value="Priced as service" />
                        <x-search-select id="study-service" name="service_id" :options="$services->map(fn ($service) => ['value' => $service->id, 'label' => $service->name, 'hint' => \App\Support\Money::format($service->cash_price)])" placeholder="Search services" required />
                    </div>
                    <div class="sm:col-span-2"><x-primary-button>Add study</x-primary-button></div>
                </form>
            @endif
            <ul class="mt-4 max-h-64 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 text-sm">
                @foreach ($studies as $study)
                    <li class="flex justify-between gap-3 py-2"><span class="font-medium">{{ $study->name }}</span><span>{{ \App\Support\Money::format($study->service?->cash_price ?? 0) }}</span></li>
                @endforeach
            </ul>
        </section>
    </div>
</x-app-layout>
