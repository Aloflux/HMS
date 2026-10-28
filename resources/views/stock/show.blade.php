@php($select = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a class="text-sm text-brand-700" href="{{ route('stock.levels') }}">← Stock levels</a>
                <x-page-title>{{ $item->name }}</x-page-title>
                <p class="text-sm text-slate-500">
                    {{ $item->form->label() }} · dispensed by the {{ $item->unitLabel() }}{{ $item->packLabel() ? ' · bought by the '.$item->packLabel() : '' }}
                    · {{ \App\Support\Money::format($item->cash_price) }} per {{ $item->unit }} · reorder at {{ $item->describeQuantity($item->reorder_level) }} · {{ $item->sku }}
                </p>
            </div>
            @if (auth()->user()->permits('inventory.purchase'))
                <x-secondary-button x-data x-on:click="$dispatch('open-modal', 'edit-item')"><x-icon name="bx-edit" class="text-lg" />Edit item</x-secondary-button>
            @endif
        </div>
    </x-slot>

    @if (auth()->user()->permits('inventory.purchase'))
        <x-modal name="edit-item" :show="old('_modal') === 'edit-item'" max-width="2xl">
            <form method="POST" action="{{ route('inventory.items.update', $item) }}" class="p-6"
                x-data="itemFields(@js(['form' => old('form', $item->form->value), 'unit' => old('unit', $item->unit), 'unit_content' => old('unit_content', $item->unit_content), 'pack_unit' => old('pack_unit', $item->pack_unit), 'pack_size' => old('pack_size', $item->hasPacks() ? $item->pack_size : null)]), @js($formDefaults))">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="edit-item">
                <h2 class="text-lg font-bold text-slate-900">Edit {{ $item->name }}</h2>
                @if ($balances->isNotEmpty())
                    <p class="mt-1 text-sm text-slate-500">Stock is counted in {{ \Illuminate\Support\Str::plural($item->unit) }}, so that unit cannot change now. You can change the pack, price and reorder level.</p>
                @endif
                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    <x-item-fields :forms="$forms" :item="$item" prefix="edit-item" />
                    <label class="flex items-center gap-2 text-sm md:col-span-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                        Active (shown on order, prescribing and dispensing lists)
                    </label>
                </div>
                @if (old('_modal') === 'edit-item')
                    <x-input-error :messages="$errors->all()" class="mt-3" />
                @endif
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Close</x-secondary-button>
                    <x-primary-button>Save item</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif

    <section class="rounded-lg bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">On hand now</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-2 pe-4 font-medium">Store</th>
                        <th class="py-2 pe-4 font-medium">Batch</th>
                        <th class="py-2 pe-4 font-medium">Expiry</th>
                        <th class="py-2 pe-4 text-right font-medium">Cost per {{ $item->unit }}</th>
                        <th class="py-2 text-right font-medium">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $batch)
                        @php($expired = $batch->expiry_date && $batch->expiry_date->isPast())
                        @php($expiresSoon = $batch->expiry_date && $batch->expiry_date->lte(now()->addDays(\App\Services\StockReport::EXPIRY_WARNING_DAYS)))
                        <tr class="border-t border-slate-100">
                            <td class="py-2 pe-4">{{ $batch->location->name }}</td>
                            <td class="py-2 pe-4">{{ $batch->batch_number }}</td>
                            <td class="py-2 pe-4 {{ $expiresSoon ? 'font-semibold text-red-700' : '' }}">{{ $batch->expiry_date?->format('d M Y') ?? 'No expiry' }}{{ $expired ? ' · expired' : '' }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums">{{ \App\Support\Money::format($batch->unit_cost) }}</td>
                            <td class="py-2 text-right font-semibold tabular-nums">{{ $item->describeQuantity($batch->quantity) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-2 text-slate-500">None in any store.</td></tr>
                    @endforelse
                </tbody>
                @if ($balances->isNotEmpty())
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td colspan="4" class="py-2 pe-4 font-semibold">Total</td>
                            <td class="py-2 text-right font-bold tabular-nums">{{ $item->describeQuantity((int) $balances->sum('quantity')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    <section class="mt-6 rounded-lg bg-white p-5 shadow-card">
        <h2 class="font-bold text-slate-900">Stock card</h2>
        <p class="mt-1 text-sm text-slate-500">Every movement in and out, newest first. The balance is what was left after each movement{{ $locationId ? ' in this store' : ' across all stores' }}.</p>

        <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
            <div>
                <x-input-label for="card-store" value="Store" />
                <select id="card-store" name="stock_location_id" class="{{ $select }}">
                    <option value="">All stores</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected($locationId === $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="card-start" value="From" />
                <x-text-input id="card-start" type="date" name="start" :value="$start" class="mt-1 block" />
            </div>
            <div>
                <x-input-label for="card-end" value="To" />
                <x-text-input id="card-end" type="date" name="end" :value="$end" class="mt-1 block" />
            </div>
            <x-primary-button>Filter</x-primary-button>
        </form>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-2 pe-4 font-medium">When</th>
                        <th class="py-2 pe-4 font-medium">Store</th>
                        <th class="py-2 pe-4 font-medium">What happened</th>
                        <th class="py-2 pe-4 text-right font-medium">In</th>
                        <th class="py-2 pe-4 text-right font-medium">Out</th>
                        <th class="py-2 pe-4 text-right font-medium">Balance</th>
                        <th class="py-2 pe-4 font-medium">Batch</th>
                        <th class="py-2 pe-4 font-medium">Department</th>
                        <th class="py-2 pe-4 font-medium">Patient</th>
                        <th class="py-2 pe-4 font-medium">By</th>
                        <th class="py-2 font-medium">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="whitespace-nowrap py-2 pe-4">{{ $movement->created_at->format('d M Y H:i') }}</td>
                            <td class="py-2 pe-4">{{ $movement->location?->name }}</td>
                            <td class="py-2 pe-4">
                                {{ $movement->reason->label() }}
                                @if ($movement->notes)
                                    <span class="block text-xs text-slate-500">{{ $movement->notes }}</span>
                                @endif
                            </td>
                            <td class="py-2 pe-4 text-right tabular-nums text-brand-700">{{ $movement->quantity > 0 ? number_format($movement->quantity) : '' }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-red-700">{{ $movement->quantity < 0 ? number_format(abs($movement->quantity)) : '' }}</td>
                            <td class="py-2 pe-4 text-right font-semibold tabular-nums">{{ number_format($movement->balance_after) }}</td>
                            <td class="py-2 pe-4">{{ $movement->batch?->batch_number }}</td>
                            <td class="py-2 pe-4">{{ $movement->department?->name ?? '—' }}</td>
                            <td class="py-2 pe-4">{{ $movement->patient?->fullName() ?? '—' }}</td>
                            <td class="py-2 pe-4">
                                {{ $movement->performer?->name ?? '—' }}
                                @if ($movement->approver)
                                    <span class="block text-xs text-slate-500">Approved by {{ $movement->approver->name }}</span>
                                @endif
                            </td>
                            <td class="py-2">{{ $movement->referenceLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-2 text-slate-500">No movements in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $movements->links() }}</div>
    </section>
</x-app-layout>
