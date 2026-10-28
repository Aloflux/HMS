@php
    $statusLabels = [
        'out_of_stock' => ['Out of stock', 'bg-red-50 text-red-700'],
        'expired' => ['Has expired stock', 'bg-red-50 text-red-700'],
        'low' => ['Low', 'bg-amber-50 text-amber-800'],
        'expiring' => ['Expiring soon', 'bg-amber-50 text-amber-800'],
        'ok' => ['In stock', 'bg-brand-50 text-brand-800'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-title icon="bx-layer">Stock levels</x-page-title>
            @if (auth()->user()->permits('inventory.view') || auth()->user()->permits('reports.view'))
                <a class="inline-flex items-center gap-1 text-sm text-brand-700" href="{{ route('stock.usage') }}"><x-icon name="bx-line-chart-down" class="text-lg" />Usage by department</a>
            @endif
        </div>
    </x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <x-input-label for="stock-search" value="Find an item" />
            <x-text-input id="stock-search" name="search" :value="$search" class="mt-1 block" placeholder="Paracetamol" />
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm">
            <input type="checkbox" name="attention" value="1" @checked($attention) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
            Only items that need attention
        </label>
        <x-primary-button>Filter</x-primary-button>
    </form>

    <section class="rounded-lg bg-white p-5 shadow-card">
        <p class="text-sm text-slate-500">Quantity on hand in each store. "Low" means the total is at or below the item's reorder level. "Expiring soon" means a batch expires within {{ \App\Services\StockReport::EXPIRY_WARNING_DAYS }} days. Open an item to see every movement in and out.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-2 pe-4 font-medium">Item</th>
                        @foreach ($locations as $location)
                            <th class="py-2 pe-4 text-right font-medium">{{ $location->name }}</th>
                        @endforeach
                        <th class="py-2 pe-4 text-right font-medium">Total</th>
                        <th class="py-2 pe-4 text-right font-medium">Reorder at</th>
                        <th class="py-2 pe-4 text-right font-medium">Used, 30 days</th>
                        <th class="py-2 pe-4 font-medium">Nearest expiry</th>
                        <th class="py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-slate-100">
                            <td class="py-2 pe-4">
                                <a class="font-medium text-brand-700" href="{{ route('stock.levels.item', $row['item']) }}">{{ $row['item']->name }}</a>
                                <span class="block text-xs text-slate-500">{{ $row['item']->sku }} · {{ $row['item']->unit }}</span>
                            </td>
                            @foreach ($locations as $location)
                                <td class="py-2 pe-4 text-right tabular-nums {{ ($row['by_location'][$location->id] ?? 0) === 0 ? 'text-slate-300' : '' }}">{{ number_format($row['by_location'][$location->id] ?? 0) }}</td>
                            @endforeach
                            <td class="py-2 pe-4 text-right font-semibold tabular-nums">{{ number_format($row['total']) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-slate-500">{{ number_format($row['item']->reorder_level) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums">{{ number_format($row['used_30_days']) }}</td>
                            <td class="py-2 pe-4">{{ $row['nearest_expiry']?->format('d M Y') ?? '—' }}</td>
                            <td class="py-2">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusLabels[$row['status']][1] }}">{{ $statusLabels[$row['status']][0] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $locations->count() + 6 }}" class="py-3 text-slate-500">
                                No items match.
                                @if (auth()->user()->permits('inventory.purchase'))
                                    <a class="text-brand-700" href="{{ route('inventory.index') }}">Add items and stock on the Inventory page.</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
