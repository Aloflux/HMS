@props(['forms', 'item' => null, 'prefix' => 'item'])

{{--
    The description of a stock item: name, dosage form, dispensing unit, purchase pack, price and reorder level.
    Needs a parent element with x-data="itemFields(...)". Renders grid cells for a four-column form grid.
--}}
<div class="md:col-span-2">
    <x-input-label :for="$prefix.'-name'" value="Name and strength" />
    <x-text-input :id="$prefix.'-name'" name="name" class="mt-1 block w-full" :value="old('name', $item?->name)" placeholder="Amoxicillin 125mg/5ml suspension" required />
</div>
<div class="md:col-span-2">
    <x-input-label :for="$prefix.'-form'" value="Dosage form" />
    <select id="{{ $prefix }}-form" name="form" x-model="form" @change="applyDefaults()" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
        @foreach ($forms as $form)
            <option value="{{ $form->value }}">{{ $form->label() }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Choosing a form fills in the usual unit and pack. Change them to match what you buy.</p>
</div>
<div>
    <x-input-label :for="$prefix.'-unit'" value="Dispensed and counted in" />
    <x-text-input :id="$prefix.'-unit'" name="unit" x-model="unit" class="mt-1 block w-full" placeholder="tablet, bottle, ml, tube" required />
    <p class="mt-1 text-xs text-slate-500">The smallest amount you hand out. Stock and prices use it.</p>
</div>
<div>
    <x-input-label :for="$prefix.'-content'" value="Each one contains" />
    <x-text-input :id="$prefix.'-content'" name="unit_content" x-model="content" class="mt-1 block w-full" placeholder="100 ml, 400 g (optional)" />
</div>
<div>
    <x-input-label :for="$prefix.'-pack-unit'" value="Bought in" />
    <x-text-input :id="$prefix.'-pack-unit'" name="pack_unit" x-model="packUnit" class="mt-1 block w-full" placeholder="tin, box, carton (optional)" />
</div>
<div>
    <x-input-label :for="$prefix.'-pack-size'" value="How many in each" />
    <x-text-input :id="$prefix.'-pack-size'" name="pack_size" type="number" min="1" x-model="packSize" class="mt-1 block w-full" placeholder="1000" />
    <p class="mt-1 text-xs text-slate-500" x-show="meta.pack_size > 1" x-text="'1 ' + meta.pack_unit + ' = ' + meta.pack_size.toLocaleString() + ' ' + meta.units"></p>
</div>
<div>
    <label for="{{ $prefix }}-price" class="block text-sm font-medium text-slate-700" x-text="'Price to patient per ' + meta.unit + ' (UGX)'">Price to patient (UGX)</label>
    <x-text-input :id="$prefix.'-price'" name="cash_price" type="number" min="0" class="mt-1 block w-full" :value="old('cash_price', $item?->cash_price ?? 0)" required />
</div>
<div>
    <label for="{{ $prefix }}-reorder" class="block text-sm font-medium text-slate-700" x-text="'Reorder when stock falls to (' + meta.units + ')'">Reorder level</label>
    <x-text-input :id="$prefix.'-reorder'" name="reorder_level" type="number" min="0" class="mt-1 block w-full" :value="old('reorder_level', $item?->reorder_level ?? 0)" required />
</div>
