@props(['stores', 'items'])

<form method="POST" action="{{ route('inventory.return') }}" class="mt-4 grid gap-3 md:grid-cols-4 md:items-end">
    @csrf
    {{ $slot }}
    <div>
        <x-input-label for="return-store" value="From store" />
        <select id="return-store" name="stock_location_id" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
            @foreach ($stores as $location)
                <option value="{{ $location->id }}" @selected((string) old('stock_location_id') === (string) $location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="return-item" value="Item" />
        <x-search-select id="return-item" name="item_id" :options="$items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->unit])" :selected="old('item_id')" placeholder="Search items" required />
    </div>
    <div>
        <x-input-label for="return-quantity" value="Quantity" />
        <x-text-input id="return-quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" required />
    </div>
    <div>
        <x-input-label for="return-notes" value="Reason" />
        <x-text-input id="return-notes" name="notes" class="mt-1 block w-full" placeholder="Overstocked, near expiry…" required />
    </div>
    <div class="md:col-span-4"><x-primary-button>Return to main store</x-primary-button></div>
</form>
