@php
    $user = auth()->user();
    $canRestock = $store && $user->permits('inventory.requisition');
    $canAddStock = $store && $user->permits('inventory.purchase');
    $reopen = old('_modal');
    $waiting = $items->filter(fn ($line) => $line->quantity - (int) $line->dispensations->sum('quantity') > 0)->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-title icon="bx-capsule">Pharmacy</x-page-title>
            <div class="flex flex-wrap items-center gap-2">
                <x-secondary-button x-data x-on:click="$dispatch('open-modal', 'pharmacy-shelf')">
                    <x-icon name="bx-grid-alt" class="text-lg" />
                    Shelf
                    <span class="rounded-full bg-slate-100 px-2 text-xs text-slate-600">{{ $onHand->count() }}</span>
                </x-secondary-button>
                @if ($canRestock)
                    <x-secondary-button x-data x-on:click="$dispatch('open-modal', 'request-stock')">
                        <x-icon name="bx-cart-add" class="text-lg" />
                        Request stock
                        @if ($suggestions->isNotEmpty())
                            <span class="rounded-full bg-amber-100 px-2 text-xs text-amber-800">{{ $suggestions->count() }} low</span>
                        @endif
                    </x-secondary-button>
                    <x-secondary-button x-data x-on:click="$dispatch('open-modal', 'return-stock')">
                        <x-icon name="bx-undo" class="text-lg" />
                        Return stock
                    </x-secondary-button>
                @endif
                @if ($canAddStock)
                    <x-secondary-button x-data x-on:click="$dispatch('open-modal', 'add-stock')">
                        <x-icon name="bx-plus-circle" class="text-lg" />
                        Add stock
                    </x-secondary-button>
                @endif
                <a class="inline-flex items-center gap-1 px-2 text-sm text-brand-700" href="{{ route('stock.levels') }}"><x-icon name="bx-layer" class="text-lg" />Stock levels</a>
            </div>
        </div>
    </x-slot>

    @if ($suggestions->isNotEmpty())
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span>
                <span class="font-semibold">{{ $suggestions->count() }} {{ \Illuminate\Support\Str::plural('medicine', $suggestions->count()) }} running low on the shelf:</span>
                {{ $suggestions->take(3)->map(fn ($row) => $row['item']->name)->join(', ') }}{{ $suggestions->count() > 3 ? ' and '.($suggestions->count() - 3).' more' : '' }}
            </span>
            @if ($canRestock)
                <button type="button" class="font-semibold underline" x-data x-on:click="$dispatch('open-modal', 'request-stock')">Review and restock</button>
            @endif
        </div>
    @endif

    <section class="rounded-lg bg-white shadow-card">
        <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-900">Prescriptions</h2>
            <p class="text-sm text-slate-500">{{ $waiting }} waiting to dispense · {{ $items->count() - $waiting }} waiting for collection</p>
        </div>

        <ul class="divide-y divide-slate-100">
            @forelse ($items as $line)
                @php
                    $dispensed = (int) $line->dispensations->sum('quantity');
                    $remaining = max(0, $line->quantity - $dispensed);
                    $medicine = $line->item;
                    $shelfQty = (int) ($onHand[$line->item_id] ?? 0);
                    $visit = $line->prescription->visit;
                @endphp
                <li class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                    <div>
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-900">{{ $medicine->name }}</span>
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ $medicine->form->shortLabel() }}</span>
                        </p>
                        <p class="text-sm text-slate-500">{{ $line->dose }} · {{ $line->frequency }} · {{ $line->duration_days }} days</p>
                    </div>
                    <div class="text-sm">
                        <a class="font-medium text-brand-700" href="{{ route('visits.show', $visit) }}">{{ $visit->patient->fullName() }}</a>
                        <span class="text-slate-500">· Visit {{ $visit->visit_number }}</span>
                        @if ($remaining > 0)
                            <p class="{{ $shelfQty < $remaining ? 'font-medium text-red-700' : 'text-slate-500' }}">
                                {{ number_format($remaining) }} of {{ number_format($line->quantity) }} {{ \Illuminate\Support\Str::plural($medicine->unit, $line->quantity) }}{{ $medicine->unit_content ? ' ('.$medicine->unit_content.')' : '' }} to dispense · {{ $medicine->describeQuantity($shelfQty) }} on the shelf
                            </p>
                        @else
                            <p class="text-slate-500">All {{ $medicine->describeQuantity($line->quantity) }} dispensed</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 sm:justify-end">
                        @if ($remaining > 0)
                            <span class="inline-flex whitespace-nowrap rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800">{{ $dispensed > 0 ? 'Part dispensed' : 'Waiting' }}</span>
                            @if ($user->permits('pharmacy.manage'))
                                <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'dispense-{{ $line->id }}')">Dispense</x-primary-button>
                            @endif
                        @else
                            <span class="inline-flex whitespace-nowrap rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-800">Waiting for collection</span>
                        @endif
                    </div>
                </li>

                @if ($remaining > 0 && $user->permits('pharmacy.manage'))
                    <x-modal :name="'dispense-'.$line->id" :show="$reopen === 'dispense-'.$line->id" focusable>
                        <form method="POST" action="{{ route('pharmacy.dispense', $line) }}" class="p-6"
                            x-data="{
                                qty: @js($reopen === 'dispense-'.$line->id ? old('quantity') : $remaining),
                                handling: @js($medicine->form->handling()),
                                unit: @js($medicine->unit),
                                content: @js($medicine->unit_content),
                                packUnit: @js($medicine->hasPacks() ? $medicine->pack_unit : null),
                                get instruction() {
                                    const q = Number(this.qty) || 0;
                                    const units = window.pluralize(this.unit, q);
                                    const source = this.packUnit ? ' from the ' + this.packUnit : '';

                                    if (this.handling === 'count') return 'Count out ' + q.toLocaleString() + ' ' + units + source;
                                    if (this.handling === 'measure') return 'Measure out ' + q.toLocaleString() + ' ' + this.unit + source;

                                    return 'Hand over ' + q.toLocaleString() + ' ' + units + (this.content ? ' (' + this.content + (q > 1 ? ' each' : '') + ')' : '');
                                },
                            }">
                            @csrf
                            <input type="hidden" name="_modal" value="dispense-{{ $line->id }}">
                            <h2 class="text-lg font-bold text-slate-900">Dispense {{ $medicine->name }}</h2>
                            <p class="text-sm text-slate-500">For {{ $visit->patient->fullName() }} · Visit {{ $visit->visit_number }}</p>
                            @if ($visit->patient->allergies)
                                <p class="mt-2 rounded-md bg-red-50 px-3 py-2 text-sm font-medium text-red-700">Allergies: {{ $visit->patient->allergies }}</p>
                            @endif

                            <dl class="mt-4 grid grid-cols-2 gap-3 rounded-md bg-slate-50 p-4 text-sm sm:grid-cols-3">
                                <div><dt class="text-slate-500">Dose</dt><dd class="font-medium">{{ $line->dose }}</dd></div>
                                <div><dt class="text-slate-500">Frequency</dt><dd class="font-medium">{{ $line->frequency }}</dd></div>
                                <div><dt class="text-slate-500">Duration</dt><dd class="font-medium">{{ $line->duration_days }} days</dd></div>
                                <div><dt class="text-slate-500">Prescribed</dt><dd class="font-medium">{{ $medicine->describeQuantity($line->quantity) }}</dd></div>
                                <div><dt class="text-slate-500">Already dispensed</dt><dd class="font-medium">{{ number_format($dispensed) }}</dd></div>
                                <div><dt class="text-slate-500">On the shelf</dt><dd class="font-medium {{ $shelfQty < $remaining ? 'text-red-700' : '' }}">{{ $medicine->describeQuantity($shelfQty) }}</dd></div>
                                <div><dt class="text-slate-500">Form</dt><dd class="font-medium">{{ $medicine->form->label() }}</dd></div>
                                <div><dt class="text-slate-500">Price per {{ $medicine->unit }}</dt><dd class="font-medium">{{ \App\Support\Money::format($medicine->cash_price) }}</dd></div>
                            </dl>

                            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                <div>
                                    <x-input-label :for="'quantity-'.$line->id" value="Quantity to dispense" />
                                    <x-text-input :id="'quantity-'.$line->id" type="number" name="quantity" min="1" :max="$remaining" x-model="qty" class="mt-1 block w-full" required />
                                    <p class="mt-1 text-xs text-slate-500">In {{ \Illuminate\Support\Str::plural($medicine->unit) }}. Up to {{ number_format($remaining) }}.</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label :for="'substitute-'.$line->id" value="Substitute medicine" />
                                    <x-search-select :id="'substitute-'.$line->id" name="substitute_item_id" :options="$substitutes->reject(fn ($substitute) => $substitute->id === $line->item_id)->map(fn ($substitute) => ['value' => $substitute->id, 'label' => $substitute->name, 'hint' => $substitute->unitLabel().' · '.\App\Support\Money::format($substitute->cash_price)])" :selected="$reopen === 'dispense-'.$line->id ? old('substitute_item_id') : null" :empty-label="'Dispense '.$line->item->name" placeholder="Search for a substitute" />
                                </div>
                            </div>
                            <p class="mt-4 flex items-center gap-2 rounded-md bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800">
                                <x-icon name="bx-info-circle" class="text-lg" />
                                <span x-text="instruction"></span>
                            </p>
                            @if ($reopen === 'dispense-'.$line->id)
                                <x-input-error :messages="$errors->all()" class="mt-3" />
                            @endif

                            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                                <button form="cancel-{{ $line->id }}" class="text-sm text-red-700" onclick="return confirm('Cancel this prescription line? It will not be dispensed.')">Cancel this prescription line</button>
                                <div class="flex gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Close</x-secondary-button>
                                    <x-primary-button>Dispense</x-primary-button>
                                </div>
                            </div>
                        </form>
                        <form id="cancel-{{ $line->id }}" method="POST" action="{{ route('pharmacy.cancel', $line) }}">@csrf</form>
                    </x-modal>
                @endif
            @empty
                <li class="px-5 py-6 text-sm text-slate-500">No prescriptions are waiting to dispense or collect.</li>
            @endforelse
        </ul>
    </section>

    <x-modal name="pharmacy-shelf" max-width="2xl">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Pharmacy shelf</h2>
                    <p class="text-sm text-slate-500">Working stock in the pharmacy. The batch that expires first is used first.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600" x-on:click="$dispatch('close')" aria-label="Close"><x-icon name="bx-x" class="text-2xl" /></button>
            </div>
            <div class="mt-4 max-h-[60vh] overflow-auto" x-data="{ filter: '' }">
                <input type="search" x-model="filter" placeholder="Filter medicines" class="mb-3 block w-full rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0">
                <table class="min-w-full text-left text-sm">
                    <thead class="sticky top-0 bg-white text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pe-4 font-medium">Medicine</th>
                            <th class="py-2 pe-4 font-medium">Batch</th>
                            <th class="py-2 pe-4 font-medium">Expiry</th>
                            <th class="py-2 text-right font-medium">On hand</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shelf as $batch)
                            @php($expiresSoon = $batch->expiry_date && $batch->expiry_date->lte(now()->addDays(\App\Services\StockReport::EXPIRY_WARNING_DAYS)))
                            <tr class="border-t border-slate-100" x-show="@js(\Illuminate\Support\Str::lower($batch->item->name)).includes(filter.toLowerCase())">
                                <td class="py-2 pe-4 font-medium"><a class="text-brand-700" href="{{ route('stock.levels.item', ['item' => $batch->item, 'stock_location_id' => $batch->stock_location_id]) }}">{{ $batch->item->name }}</a></td>
                                <td class="py-2 pe-4">{{ $batch->batch_number }}</td>
                                <td class="py-2 pe-4 {{ $expiresSoon ? 'font-semibold text-red-700' : '' }}">{{ $batch->expiry_date?->format('d M Y') ?? 'No expiry' }}</td>
                                <td class="py-2 text-right tabular-nums">{{ $batch->item->describeQuantity($batch->quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-slate-500">The pharmacy shelf is empty. Request stock from the main store.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>

    @if ($canRestock)
        <x-modal name="request-stock" :show="$reopen === 'request-stock'" max-width="2xl">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Request stock from the main store</h2>
                        <p class="text-sm text-slate-500">Low items are filled in with enough to reach twice their reorder level. Change anything before sending.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" x-on:click="$dispatch('close')" aria-label="Close"><x-icon name="bx-x" class="text-2xl" /></button>
                </div>
                @if ($suggestions->isNotEmpty())
                    <ul class="mt-4 grid gap-1 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                        @foreach ($suggestions as $row)
                            <li><span class="font-medium">{{ $row['item']->name }}</span> · {{ number_format($row['on_hand']) }} left, reorder at {{ number_format($row['item']->reorder_level) }} · {{ $row['item']->describeQuantity($row['main_on_hand']) }} in the main store</li>
                        @endforeach
                    </ul>
                @endif
                <x-requisition-form :stores="collect([$store])" :items="$stockItems" :suggestions="[$store->id => $suggestions]">
                    <input type="hidden" name="_modal" value="request-stock">
                </x-requisition-form>
            </div>
        </x-modal>
    @endif

    @if ($canAddStock)
        <x-modal name="add-stock" :show="$reopen === 'add-stock'" max-width="2xl">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Add stock to the pharmacy</h2>
                        <p class="text-sm text-slate-500">For a delivery or donation that comes straight to the pharmacy, or stock already on the shelf. To take stock from the main store, use Request stock instead.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" x-on:click="$dispatch('close')" aria-label="Close"><x-icon name="bx-x" class="text-2xl" /></button>
                </div>
                <x-add-stock-form :stores="collect([$store])" :items="$stockItems" :default-store="$store">
                    <input type="hidden" name="_modal" value="add-stock">
                </x-add-stock-form>
            </div>
        </x-modal>
    @endif

    @if ($canRestock)
        <x-modal name="return-stock" :show="$reopen === 'return-stock'" max-width="2xl">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Return stock to the main store</h2>
                        <p class="text-sm text-slate-500">For stock the pharmacy does not need. The batch and expiry date go back with it.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" x-on:click="$dispatch('close')" aria-label="Close"><x-icon name="bx-x" class="text-2xl" /></button>
                </div>
                <x-return-stock-form :stores="collect([$store])" :items="$stockItems">
                    <input type="hidden" name="_modal" value="return-stock">
                </x-return-stock-form>
            </div>
        </x-modal>
    @endif
</x-app-layout>
