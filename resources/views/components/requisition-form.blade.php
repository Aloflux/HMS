@props(['stores', 'items', 'suggestions'])

@php
    $suggestedLines = collect($suggestions)->map(fn ($rows) => collect($rows)->map(fn ($row) => [
        'item_id' => (string) $row['item']->id,
        'quantity' => (string) max(1, $row['suggested']),
    ])->values());
    $initialStore = (string) (old('stock_location_id') ?? $stores->first()?->id);
    $initialLines = old('lines')
        ? array_values(old('lines'))
        : ($suggestedLines->get((int) $initialStore)?->all() ?: [['item_id' => '', 'quantity' => '']]);
@endphp

<form method="POST" action="{{ route('inventory.requisition') }}" class="mt-4 grid gap-3"
    x-data="{
        store: @js($initialStore),
        lines: @js($initialLines),
        suggestions: @js($suggestedLines),
        add() { this.lines.push({ item_id: '', quantity: '' }) },
        remove(index) { this.lines.splice(index, 1); if (! this.lines.length) { this.add() } },
        fill() {
            const rows = this.suggestions[this.store] ?? [];
            this.lines = rows.length ? rows.map(row => ({ ...row })) : [{ item_id: '', quantity: '' }];
        },
    }">
    @csrf
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
        <div class="grid gap-3 md:grid-cols-[1fr_10rem_auto] md:items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700" :for="'requisition-item-' + index">Item</label>
                <select :id="'requisition-item-' + index" :name="'lines[' + index + '][item_id]'" x-model="line.item_id" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                    <option value="">Choose an item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" :for="'requisition-quantity-' + index">Quantity</label>
                <input :id="'requisition-quantity-' + index" :name="'lines[' + index + '][quantity]'" x-model="line.quantity" type="number" min="1" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
            </div>
            <button type="button" class="pb-2 text-sm text-red-700" @click="remove(index)">Remove</button>
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
