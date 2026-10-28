@props(['stores', 'items', 'defaultStore' => null])

@php
    $select = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm';
    $itemOptions = $items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->unit.($item->sku ? ' · '.$item->sku : '')]);
@endphp

<form method="POST" action="{{ route('inventory.stock.add') }}" class="mt-4 grid gap-3 md:grid-cols-4">
    @csrf
    {{ $slot }}
    <div class="md:col-span-2">
        <x-input-label for="stock-item" value="Item" />
        <x-search-select id="stock-item" name="item_id" :options="$itemOptions" :selected="old('item_id')" placeholder="Search items" required />
    </div>
    <div>
        <x-input-label for="stock-store" value="Into store" />
        <select id="stock-store" name="stock_location_id" class="{{ $select }}">
            @foreach ($stores as $location)
                <option value="{{ $location->id }}" @selected((string) old('stock_location_id', $defaultStore?->id) === (string) $location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="stock-reason" value="Where it came from" />
        <select id="stock-reason" name="reason" class="{{ $select }}">
            <option value="purchase" @selected(old('reason') === 'purchase')>Delivery without a purchase order</option>
            <option value="donation" @selected(old('reason') === 'donation')>Donation</option>
            <option value="opening" @selected(old('reason') === 'opening')>Opening balance (already on the shelf)</option>
        </select>
    </div>
    <div>
        <x-input-label for="stock-quantity" value="Quantity" />
        <x-text-input id="stock-quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity')" required />
    </div>
    <div>
        <x-input-label for="stock-batch" value="Batch number" />
        <x-text-input id="stock-batch" name="batch_number" class="mt-1 block w-full" :value="old('batch_number')" placeholder="Optional" />
    </div>
    <div>
        <x-input-label for="stock-expiry" value="Expiry date" />
        <x-text-input id="stock-expiry" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" />
    </div>
    <div>
        <x-input-label for="stock-cost" value="Unit cost (UGX)" />
        <x-text-input id="stock-cost" name="unit_cost" type="number" min="0" class="mt-1 block w-full" :value="old('unit_cost')" placeholder="0" />
    </div>
    <div class="md:col-span-3">
        <x-input-label for="stock-notes" value="Notes" />
        <x-text-input id="stock-notes" name="notes" class="mt-1 block w-full" :value="old('notes')" placeholder="Supplier, donor, delivery note number" />
    </div>
    <div class="flex items-end"><x-primary-button>Add stock</x-primary-button></div>
</form>
