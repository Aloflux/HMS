@php
    $user = auth()->user();
    $receivingStores = $locations->where('kind', '!=', 'main');
@endphp

<x-app-layout>
    <x-slot name="header"><x-page-title>Inventory</x-page-title></x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($locations as $location)
            @php($stocked = $location->batches->where('quantity', '>', 0)->sortBy(fn ($batch) => $batch->item->name))
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">{{ $location->name }}</h2>
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
                                    <td class="py-2">{{ $batch->quantity }} {{ \Illuminate\Support\Str::plural($batch->item->unit, $batch->quantity) }}</td>
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
        <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Purchase orders</h2>
            <form method="POST" action="{{ route('inventory.order') }}" class="mt-4 grid gap-3 md:grid-cols-4" x-data="{ newSupplier: @js($suppliers->isEmpty() || filled(old('supplier_name'))) }">
                @csrf
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between">
                        <x-input-label for="supplier" value="Supplier" />
                        @if ($suppliers->isNotEmpty())
                            <button type="button" class="text-xs text-brand-700" @click="newSupplier = ! newSupplier" x-text="newSupplier ? 'Choose an existing supplier' : 'Add a new supplier'"></button>
                        @endif
                    </div>
                    <template x-if="! newSupplier">
                        <select id="supplier" name="supplier_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm" required>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </template>
                    <template x-if="newSupplier">
                        <x-text-input id="supplier" name="supplier_name" class="mt-1 block w-full" :value="old('supplier_name')" placeholder="New supplier's name" required />
                    </template>
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="order-item" value="Item" />
                    <select id="order-item" name="item_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>{{ $item->name }} ({{ $item->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="order-quantity" value="Quantity" />
                    <x-text-input id="order-quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" :value="old('quantity')" required />
                </div>
                <div>
                    <x-input-label for="order-cost" value="Unit cost (UGX)" />
                    <x-text-input id="order-cost" name="unit_cost" type="number" min="0" class="mt-1 block w-full" :value="old('unit_cost')" required />
                </div>
                <div class="flex items-end md:col-span-2"><x-primary-button>Send order</x-primary-button></div>
            </form>

            <div class="mt-6 grid gap-3">
                @foreach ($orders as $order)
                    <div class="rounded-md border border-slate-200 p-4 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">{{ $order->number }} · {{ $order->supplier->name }}</span>
                            <x-status :value="$order->status" />
                        </div>
                        @foreach ($order->lines as $line)
                            <div class="mt-3 border-t border-slate-100 pt-3">
                                <p>{{ $line->item->name }} · received {{ $line->received_quantity }} of {{ $line->quantity }} {{ \Illuminate\Support\Str::plural($line->item->unit, $line->quantity) }}</p>
                                @if ($line->received_quantity < $line->quantity)
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
                                        <div>
                                            <x-input-label :for="'received-'.$line->id" value="Quantity received" />
                                            <x-text-input :id="'received-'.$line->id" name="quantity" type="number" min="1" :max="$line->quantity - $line->received_quantity" :value="$line->quantity - $line->received_quantity" class="mt-1 block w-full" required />
                                        </div>
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

    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Requisitions from the main store</h2>
        @if ($user->permits('inventory.requisition'))
            <form method="POST" action="{{ route('inventory.requisition') }}" class="mt-4 grid gap-3 md:grid-cols-4">
                @csrf
                <div>
                    <x-input-label for="requisition-store" value="For store" />
                    <select id="requisition-store" name="stock_location_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        @foreach ($receivingStores as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="requisition-item" value="Item" />
                    <select id="requisition-item" name="item_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="requisition-quantity" value="Quantity" />
                    <x-text-input id="requisition-quantity" name="quantity" type="number" min="1" class="mt-1 block w-full" required />
                </div>
                <div class="flex items-end"><x-primary-button>Request</x-primary-button></div>
            </form>
        @endif
        <ul class="mt-4 divide-y divide-slate-100 text-sm">
            @forelse ($requisitions as $requisition)
                <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                    <span>
                        <span class="font-medium">{{ $requisition->number }} → {{ $requisition->location->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $requisition->lines->map(fn ($line) => $line->item->name.' × '.$line->quantity)->join(', ') }}</span>
                    </span>
                    <span class="flex items-center gap-3">
                        <x-status :value="$requisition->status" />
                        @if ($requisition->status === 'submitted' && $user->permits('inventory.purchase'))
                            <form method="POST" action="{{ route('inventory.issue', $requisition) }}">@csrf<button class="text-sm font-semibold text-brand-700">Issue</button></form>
                        @endif
                    </span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No requisitions yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Stock adjustments</h2>
        <p class="mt-1 text-sm text-slate-500">For expiry, damage, loss, or a count that does not match. An administrator approves before stock changes.</p>
        @if ($user->permits('inventory.adjust'))
            <form method="POST" action="{{ route('inventory.adjust') }}" class="mt-4 grid gap-3 md:grid-cols-3">
                @csrf
                <div>
                    <x-input-label for="adjust-store" value="Store" />
                    <select id="adjust-store" name="stock_location_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="adjust-item" value="Item" />
                    <select id="adjust-item" name="item_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="adjust-quantity" value="Change in quantity" />
                    <x-text-input id="adjust-quantity" name="quantity" type="number" class="mt-1 block w-full" placeholder="-5 to remove, 5 to add" required />
                </div>
                <div>
                    <x-input-label for="adjust-reason" value="Reason" />
                    <select id="adjust-reason" name="reason_code" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
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
                        <span class="font-medium">{{ $adjustment->item->name }} {{ $adjustment->quantity > 0 ? '+' : '' }}{{ $adjustment->quantity }}</span>
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
