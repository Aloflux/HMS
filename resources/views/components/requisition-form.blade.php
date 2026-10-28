@props(['stores', 'items', 'suggestions'])

@php
    $suggestedLines = collect($suggestions)->map(fn ($rows) => collect($rows)->map(fn ($row) => [
        'item_id' => (string) $row['item']->id,
        'quantity' => (string) ($row['suggested_packs'] ?? $row['suggested']),
        'count_in' => $row['suggested_packs'] ? 'pack' : 'unit',
    ])->values());
    $itemOptions = $items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->unitLabel().($item->packLabel() ? ' · '.$item->packLabel() : '')]);
    $itemMeta = $items->mapWithKeys(fn ($item) => [$item->id => $item->quantityMeta()]);
    $initialStore = (string) (old('stock_location_id') ?? $stores->first()?->id);
    $initialLines = old('lines')
        ? array_values(old('lines'))
        : ($suggestedLines->get((int) $initialStore)?->all() ?: [['item_id' => '', 'quantity' => '', 'count_in' => 'pack']]);
@endphp

<form method="POST" action="{{ route('inventory.requisition') }}" class="mt-4 grid gap-3"
    x-data="{
        store: @js($initialStore),
        lines: @js($initialLines),
        suggestions: @js($suggestedLines),
        metas: @js($itemMeta),
        meta(line) { return this.metas[line.item_id] ?? null },
        add() { this.lines.push({ item_id: '', quantity: '', count_in: 'pack' }) },
        remove(index) { this.lines.splice(index, 1); if (! this.lines.length) { this.add() } },
        fill() {
            const rows = this.suggestions[this.store] ?? [];
            this.lines = rows.length ? rows.map(row => ({ ...row })) : [{ item_id: '', quantity: '', count_in: 'pack' }];
        },
    }">
    @csrf
    {{ $slot }}
    <div class="grid gap-3 md:grid-cols-3">
        <div>
            <x-input-label for="requisition-store" value="For store" />
            <select id="requisition-store" name="stock_location_id" x-model="store" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                @foreach ($stores as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="requisition-notes" value="Notes" />
            <x-text-input id="requisition-notes" name="notes" class="mt-1 block w-full" :value="old('notes')" />
        </div>
    </div>

    <template x-for="(line, index) in lines" :key="index">
        <div class="grid gap-3 md:grid-cols-[1fr_16rem_auto] md:items-start">
            <div>
                <label class="block text-sm font-medium text-slate-700" :for="'requisition-item-' + index">Item</label>
                <x-search-select x-id="'requisition-item-' + index" x-name="'lines[' + index + '][item_id]'" model="line.item_id" :options="$itemOptions" placeholder="Search items" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" :for="'requisition-quantity-' + index">Quantity</label>
                <div class="mt-1 flex gap-2">
                    <input :id="'requisition-quantity-' + index" :name="'lines[' + index + '][quantity]'" x-model="line.quantity" type="number" min="1" required class="block w-full min-w-0 rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
                    <template x-if="meta(line)?.pack_size > 1">
                        <select :name="'lines[' + index + '][count_in]'" x-model="line.count_in" aria-label="Counted in" class="w-28 shrink-0 rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
                            <option value="pack" x-text="meta(line).pack_units"></option>
                            <option value="unit" x-text="meta(line).units"></option>
                        </select>
                    </template>
                    <template x-if="! (meta(line)?.pack_size > 1)">
                        <span class="self-center text-sm text-slate-500" x-text="meta(line)?.units ?? ''"></span>
                    </template>
                </div>
                <p class="mt-1 text-xs text-slate-500" x-show="meta(line)?.pack_size > 1 && line.count_in === 'pack' && line.quantity > 0"
                    x-text="'= ' + (line.quantity * (meta(line)?.pack_size ?? 1)).toLocaleString() + ' ' + (meta(line)?.units ?? '')"></p>
            </div>
            <button type="button" class="pt-7 text-sm text-red-700" @click="remove(index)">Remove</button>
        </div>
    </template>

    <x-input-error :messages="$errors->get('lines')" />
    @foreach ($errors->getMessages() as $key => $messages)
        @if (str_starts_with($key, 'lines.'))
            <x-input-error :messages="$messages" />
        @endif
    @endforeach

    <div class="flex flex-wrap items-center gap-4">
        <button type="button" class="text-sm font-semibold text-brand-700" @click="add()">Add another item</button>
        <button type="button" class="text-sm font-semibold text-brand-700" @click="fill()">Fill from reorder levels</button>
        <x-primary-button>Send requisition</x-primary-button>
    </div>
</form>
