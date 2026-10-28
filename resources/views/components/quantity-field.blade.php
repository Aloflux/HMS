@props([
    'id',
    'meta',
    'name' => 'quantity',
    'countName' => 'count_in',
    'value' => null,
    'countIn' => 'pack',
    'packsOnly' => false,
    'costName' => null,
    'costValue' => null,
    'max' => null,
])

{{--
    A quantity box that can count in purchase packs or dispensing units.
    `meta` is an Alpine expression giving {unit, units, pack_unit, pack_units, pack_size} for the item.
    Renders one grid cell, or two when `cost-name` is set, so it sits inside the parent form's grid.
--}}
<div class="contents" x-data="{
    count: @js($countIn),
    qty: @js($value),
    get m() { return ({{ $meta }}) || { unit: 'unit', units: 'units', pack_unit: null, pack_units: null, pack_size: 1 } },
    get packs() { return this.m.pack_size > 1 },
    get countIn() { return @js($packsOnly) || ! this.packs ? 'pack' : this.count },
    get countLabel() { return this.packs && this.countIn === 'pack' ? this.m.pack_unit : this.m.unit },
}">
    <div>
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700"
            x-text="@js($packsOnly) ? 'Number of ' + (packs ? m.pack_units : m.units) : 'Quantity'">Quantity</label>
        <div class="mt-1 flex gap-2">
            <input id="{{ $id }}" type="number" min="1" name="{{ $name }}" x-model="qty" required
                @if ($max) max="{{ $max }}" @endif
                class="block w-full min-w-0 rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
            @if ($packsOnly)
                <input type="hidden" name="{{ $countName }}" value="pack">
            @else
                <select name="{{ $countName }}" x-model="count" x-show="packs" aria-label="Counted in"
                    class="w-32 shrink-0 rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
                    <option value="pack" x-text="m.pack_units"></option>
                    <option value="unit" x-text="m.units"></option>
                </select>
                <span class="self-center text-sm text-slate-500" x-show="! packs" x-text="m.units"></span>
            @endif
        </div>
        <p class="mt-1 text-xs text-slate-500" x-show="packs" x-cloak
            x-text="countIn === 'pack' && qty > 0
                ? '= ' + (qty * m.pack_size).toLocaleString() + ' ' + m.units + ' (1 ' + m.pack_unit + ' = ' + m.pack_size.toLocaleString() + ')'
                : '1 ' + m.pack_unit + ' = ' + m.pack_size.toLocaleString() + ' ' + m.units"></p>
    </div>
    @if ($costName)
        <div>
            <label for="{{ $id }}-cost" class="block text-sm font-medium text-slate-700" x-text="'Cost per ' + countLabel + ' (UGX)'">Unit cost (UGX)</label>
            <input id="{{ $id }}-cost" type="number" min="0" name="{{ $costName }}" value="{{ $costValue }}" placeholder="0"
                class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
        </div>
    @endif
</div>
