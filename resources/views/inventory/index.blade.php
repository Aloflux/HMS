@php
    $user = auth()->user();
    $receivingStores = $locations->where('kind', '!=', 'main');
    $itemOptions = $items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->unitLabel().($item->packLabel() ? ' · '.$item->packLabel() : '')]);
    $itemMeta = $items->mapWithKeys(fn ($item) => [$item->id => $item->quantityMeta()]);
    $formDefaults = collect($forms)->mapWithKeys(fn ($form) => [$form->value => $form->defaults()]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-title icon="bx-package">Inventory</x-page-title>
            <span class="flex flex-wrap gap-4 text-sm">
                <a class="inline-flex items-center gap-1 text-brand-700" href="{{ route('stock.levels') }}"><x-icon name="bx-layer" class="text-lg" />Stock levels</a>
                <a class="inline-flex items-center gap-1 text-brand-700" href="{{ route('stock.usage') }}"><x-icon name="bx-line-chart-down" class="text-lg" />Usage by department</a>
            </span>
        </div>
    </x-slot>

    @php($select = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm')
    @php($activeStores = $locations->where('is_active', true))
    @php($mainStore = $activeStores->firstWhere('kind', 'main'))

    @if ($user->permits('inventory.purchase'))
        <section class="mb-6 rounded-lg bg-white p-5 shadow-card" x-data="{ tab: @js(old('_tab', $items->isEmpty() ? 'starter' : 'stock')) }">
            <h2 class="font-bold text-slate-900">Items and stock</h2>
            @if ($items->isEmpty())
                <p class="mt-1 text-sm text-amber-800">There are no items yet. Load the starter list, import your own list, or add items one at a time.</p>
            @else
                <p class="mt-1 text-sm text-slate-500">Add new items, or put stock on a shelf without a purchase order: an opening balance, a delivery, or a donation.</p>
            @endif

            <div class="mt-4 flex flex-wrap gap-2 border-b border-slate-100 text-sm">
                @foreach (['stock' => 'Add stock', 'item' => 'New item', 'import' => 'Import from CSV', 'starter' => 'Starter list'] as $key => $label)
                    <button type="button" class="-mb-px border-b-2 px-3 py-2 font-medium" :class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" @click="tab = '{{ $key }}'">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="tab === 'stock'" x-cloak>
                @if ($items->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">Add an item first.</p>
                @else
                    <x-add-stock-form :stores="$activeStores" :items="$items" :default-store="$mainStore">
                        <input type="hidden" name="_tab" value="stock">
                    </x-add-stock-form>
                @endif
            </div>

            <div x-show="tab === 'item'" x-cloak>
                <form method="POST" action="{{ route('inventory.items.store') }}" class="mt-4 grid gap-3 md:grid-cols-4"
                    x-data="itemFields(@js(['form' => old('form', 'tablet'), 'unit' => old('unit'), 'unit_content' => old('unit_content'), 'pack_unit' => old('pack_unit'), 'pack_size' => old('pack_size'), 'category' => old('category', 'medicine')]), @js($formDefaults))">
                    @csrf
                    <input type="hidden" name="_tab" value="item">
                    <x-item-fields :forms="$forms" prefix="new-item" />
                    <div>
                        <x-input-label for="new-item-category" value="Category" />
                        <select id="new-item-category" name="category" x-model="category" class="{{ $select }}">
                            <option value="medicine">Medicine</option>
                            <option value="consumable">Medical supply</option>
                            <option value="reagent">Lab reagent</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="new-item-billing" value="Billing" />
                        <select id="new-item-billing" name="billing_type" class="{{ $select }}" x-effect="$el.value = category === 'medicine' ? 'billable_medicine' : (category === 'consumable' ? 'billable_consumable' : 'non_billable')">
                            @foreach ($billingTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('billing_type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="new-item-sku" value="Code (optional)" />
                        <x-text-input id="new-item-sku" name="sku" class="mt-1 block w-full" :value="old('sku')" placeholder="Made from the name" />
                        <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                    </div>

                    <p class="text-sm font-semibold text-slate-700 md:col-span-4">Opening stock <span class="font-normal text-slate-500">· optional, if you already have some</span></p>
                    <x-quantity-field id="new-item-quantity" meta="meta" name="opening_quantity" :value="old('opening_quantity')" :count-in="old('count_in', 'unit')" cost-name="unit_cost" :cost-value="old('unit_cost')" />
                    <div>
                        <x-input-label for="new-item-store" value="In store" />
                        <select id="new-item-store" name="stock_location_id" class="{{ $select }}">
                            @foreach ($activeStores as $location)
                                <option value="{{ $location->id }}" @selected((string) old('stock_location_id', $mainStore?->id) === (string) $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="new-item-batch" value="Batch number" />
                        <x-text-input id="new-item-batch" name="batch_number" class="mt-1 block w-full" :value="old('batch_number')" />
                    </div>
                    <div>
                        <x-input-label for="new-item-expiry" value="Expiry date" />
                        <x-text-input id="new-item-expiry" name="expiry_date" type="date" class="mt-1 block w-full" :value="old('expiry_date')" />
                        <x-input-error :messages="$errors->get('expiry_date')" class="mt-1" />
                    </div>
                    <div class="flex items-end md:col-span-3"><x-primary-button>Add item</x-primary-button></div>
                </form>
            </div>

            <div x-show="tab === 'import'" x-cloak>
                <div class="mt-4 text-sm text-slate-600">
                    <p>Fill in the template in Excel or Google Sheets, save it as CSV, and upload it. Only <span class="font-medium">name</span> is required, plus either a <span class="font-medium">form</span> (such as tablet or oral_liquid) or a <span class="font-medium">unit</span>. Use <span class="font-medium">pack_unit</span> and <span class="font-medium">pack_size</span> for how you buy it, for example tin and 1000. Add a <span class="font-medium">quantity</span>, counted in dispensing units, to record opening stock at the same time.</p>
                    <p class="mt-1">An item that already exists is matched by its name or code, and only its stock is added. If any row has a mistake, nothing is saved and the rows to fix are listed.</p>
                    <a class="mt-2 inline-flex items-center gap-1 font-semibold text-brand-700" href="{{ route('inventory.items.template') }}"><x-icon name="bx-download" class="text-lg" />Download the template</a>
                </div>
                <form method="POST" action="{{ route('inventory.items.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="_tab" value="import">
                    <div>
                        <x-input-label for="import-file" value="CSV file" />
                        <input id="import-file" name="file" type="file" accept=".csv,text/csv" required class="mt-1 block text-sm">
                    </div>
                    <x-primary-button>Import</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('file')" class="mt-3" />
            </div>

            <div x-show="tab === 'starter'" x-cloak>
                <p class="mt-4 text-sm text-slate-600">Adds {{ count(\App\Services\ItemCatalogue::starterItems()) }} medicines, supplies, and lab reagents that most facilities stock, with suggested prices and reorder levels. Items you already have are skipped. You can change prices afterwards in the price list.</p>
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer text-brand-700">See the list</summary>
                    <ul class="mt-2 grid gap-1 text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (\App\Services\ItemCatalogue::starterItems() as $starter)
                            <li>{{ $starter['name'] }} · {{ $starter['unit'] }}</li>
                        @endforeach
                    </ul>
                </details>
                <form method="POST" action="{{ route('inventory.items.starter') }}" class="mt-4">
                    @csrf
                    <x-primary-button>Load the starter list</x-primary-button>
                </form>
            </div>
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($locations as $location)
            @php($stocked = $location->batches->where('quantity', '>', 0)->sortBy(fn ($batch) => $batch->item->name))
            <section class="rounded-lg bg-white p-5 shadow-card">
                <h2 class="font-bold text-slate-900">{{ $location->name }}</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="py-2 pe-3 font-medium">Item</th>
                                <th class="py-2 pe-3 font-medium">Batch</th>
                                <th class="py-2 pe-3 font-medium">Expiry</th>
                                <th class="py-2 font-medium">On hand</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocked as $batch)
                                @php($expiresSoon = $batch->expiry_date && $batch->expiry_date->lte(now()->addDays(90)))
                                <tr class="border-t border-slate-100">
                                    <td class="py-2 pe-3 font-medium">{{ $batch->item->name }}</td>
                                    <td class="py-2 pe-3">{{ $batch->batch_number }}</td>
                                    <td class="py-2 pe-3 {{ $expiresSoon ? 'font-semibold text-red-700' : '' }}">{{ $batch->expiry_date?->format('d M Y') ?? 'No expiry' }}</td>
                                    <td class="py-2">{{ $batch->item->describeQuantity($batch->quantity) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-2 text-slate-500">Nothing in this store.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    @if ($user->permits('inventory.purchase'))
        <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Purchase orders</h2>
            <form method="POST" action="{{ route('inventory.order') }}" class="mt-4 grid gap-3 md:grid-cols-4" x-data="{ newSupplier: @js($suppliers->isEmpty() || filled(old('supplier_name'))), itemId: @js((string) old('item_id', '')), metas: @js($itemMeta) }">
                @csrf
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between">
                        <x-input-label for="supplier" value="Supplier" />
                        @if ($suppliers->isNotEmpty())
                            <button type="button" class="text-xs text-brand-700" @click="newSupplier = ! newSupplier" x-text="newSupplier ? 'Choose an existing supplier' : 'Add a new supplier'"></button>
                        @endif
                    </div>
                    <template x-if="! newSupplier">
                        <x-search-select id="supplier" name="supplier_id" :options="$suppliers->map(fn ($supplier) => ['value' => $supplier->id, 'label' => $supplier->name, 'hint' => $supplier->phone])" :selected="old('supplier_id')" placeholder="Search suppliers" required />
                    </template>
                    <template x-if="newSupplier">
                        <x-text-input id="supplier" name="supplier_name" class="mt-1 block w-full" :value="old('supplier_name')" placeholder="New supplier's name" required />
                    </template>
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="order-item" value="Item" />
                    <x-search-select id="order-item" name="item_id" model="itemId" :options="$itemOptions" placeholder="Search items" required />
                </div>
                <x-quantity-field id="order-quantity" meta="metas[itemId]" :value="old('quantity')" packs-only cost-name="unit_cost" :cost-value="old('unit_cost')" />
                <div class="flex items-end md:col-span-2"><x-primary-button>Send order</x-primary-button></div>
            </form>

            <div class="mt-6 grid gap-3">
                @foreach ($orders as $order)
                    <div class="rounded-md bg-slate-50 p-4 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">{{ $order->number }} · {{ $order->supplier->name }}</span>
                            <x-status :value="$order->status" />
                        </div>
                        @foreach ($order->lines as $line)
                            <div class="mt-3 border-t border-slate-100 pt-3">
                                @php($ordered = $line->orderedItem())
                                @php($outstanding = $line->quantity - $line->received_quantity)
                                @php($wholePacks = $ordered->hasPacks() && $outstanding % $ordered->pack_size === 0)
                                <p><span class="font-medium">{{ $line->item->name }}</span> · ordered {{ $ordered->describeQuantity($line->quantity) }} · received {{ $ordered->describeQuantity($line->received_quantity) }}</p>
                                @if ($outstanding > 0)
                                    <form method="POST" action="{{ route('inventory.receive', $order) }}" class="mt-2 grid gap-3 md:grid-cols-4">
                                        @csrf
                                        <input type="hidden" name="purchase_order_line_id" value="{{ $line->id }}">
                                        <div>
                                            <x-input-label :for="'batch-'.$line->id" value="Batch number" />
                                            <x-text-input :id="'batch-'.$line->id" name="batch_number" class="mt-1 block w-full" required />
                                        </div>
                                        <div>
                                            <x-input-label :for="'expiry-'.$line->id" value="Expiry date" />
                                            <x-text-input :id="'expiry-'.$line->id" type="date" name="expiry_date" class="mt-1 block w-full" />
                                        </div>
                                        <x-quantity-field :id="'received-'.$line->id" :meta="\Illuminate\Support\Js::from($ordered->quantityMeta())->toHtml()" :value="$wholePacks ? intdiv($outstanding, $ordered->pack_size) : $outstanding" :count-in="$wholePacks ? 'pack' : 'unit'" />
                                        <div class="flex items-end"><x-primary-button>Receive</x-primary-button></div>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">Requisitions from the main store</h2>
        <p class="mt-1 text-sm text-slate-500">A store asks the main store for stock. If the main store is short, the storekeeper issues what is there and the rest stays open.</p>
        @if ($user->permits('inventory.requisition'))
            <x-requisition-form :stores="$receivingStores->where('is_active', true)" :items="$items" :suggestions="$suggestions" />
        @endif
        <ul class="mt-4 divide-y divide-slate-100 text-sm">
            @forelse ($requisitions as $requisition)
                <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                    <span>
                        <span class="font-medium">{{ $requisition->number }} → {{ $requisition->location->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $requisition->lines->map(fn ($line) => $line->item->name.' · issued '.number_format($line->quantity_issued).' of '.$line->item->describeQuantity($line->quantity_requested))->join(', ') }}</span>
                    </span>
                    <span class="flex items-center gap-3">
                        <x-status :value="$requisition->status" />
                        @if (in_array($requisition->status, ['submitted', 'partial'], true) && $user->permits('inventory.purchase'))
                            <form method="POST" action="{{ route('inventory.issue', $requisition) }}">@csrf<button class="text-sm font-semibold text-brand-700">{{ $requisition->status === 'partial' ? 'Issue the rest' : 'Issue' }}</button></form>
                        @endif
                    </span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No requisitions yet.</li>
            @endforelse
        </ul>
    </section>

    @if ($user->permits('inventory.requisition'))
        <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Return stock to the main store</h2>
            <p class="mt-1 text-sm text-slate-500">For stock a store no longer needs. Batches and expiry dates move back with it.</p>
            <x-return-stock-form :stores="$receivingStores" :items="$items" />
        </section>
    @endif

    <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">Stock adjustments</h2>
        <p class="mt-1 text-sm text-slate-500">For expiry, damage, loss, or a count that does not match. An administrator approves before stock changes.</p>
        @if ($user->permits('inventory.adjust'))
            <form method="POST" action="{{ route('inventory.adjust') }}" class="mt-4 grid gap-3 md:grid-cols-3">
                @csrf
                <div>
                    <x-input-label for="adjust-store" value="Store" />
                    <select id="adjust-store" name="stock_location_id" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="adjust-item" value="Item" />
                    <x-search-select id="adjust-item" name="item_id" :options="$itemOptions" placeholder="Search items" required />
                </div>
                <div>
                    <x-input-label for="adjust-quantity" value="Change in quantity" />
                    <x-text-input id="adjust-quantity" name="quantity" type="number" class="mt-1 block w-full" placeholder="-5 to remove, 5 to add" required />
                    <p class="mt-1 text-xs text-slate-500">In dispensing units: tablets, bottles, ml, not tins or cartons.</p>
                </div>
                <div>
                    <x-input-label for="adjust-reason" value="Reason" />
                    <select id="adjust-reason" name="reason_code" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        <option value="expiry">Expired</option>
                        <option value="damage">Damaged</option>
                        <option value="loss">Lost</option>
                        <option value="count">Count did not match</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="adjust-notes" value="Notes" />
                    <x-text-input id="adjust-notes" name="notes" class="mt-1 block w-full" />
                </div>
                <div class="flex items-end"><x-primary-button>Request adjustment</x-primary-button></div>
            </form>
        @endif
        <ul class="mt-4 divide-y divide-slate-100 text-sm">
            @forelse ($adjustments as $adjustment)
                <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                    <span>
                        <span class="font-medium">{{ $adjustment->item->name }} {{ $adjustment->quantity > 0 ? '+' : '−' }}{{ $adjustment->item->describeQuantity(abs($adjustment->quantity)) }}</span>
                        <span class="block text-xs text-slate-500">{{ $adjustment->location?->name }} · {{ ['expiry' => 'Expired', 'damage' => 'Damaged', 'loss' => 'Lost', 'count' => 'Count did not match'][$adjustment->reason_code] ?? $adjustment->reason_code }}{{ $adjustment->notes ? ' · '.$adjustment->notes : '' }}</span>
                    </span>
                    <span class="flex items-center gap-3">
                        <x-status :value="$adjustment->status" />
                        @if ($adjustment->status === 'pending' && $user->permits('inventory.approve'))
                            <form method="POST" action="{{ route('inventory.approve', $adjustment) }}">@csrf<button class="text-sm font-semibold text-brand-700">Approve</button></form>
                        @endif
                    </span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No adjustments yet.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
