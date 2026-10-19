<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-package">Inventory</x-page-title></x-slot>
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($locations as $location)
            <section class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="font-semibold">{{ $location->name }}</h2>
                <ul class="mt-2 text-sm">
                    @forelse ($location->batches as $batch)
                        <li>{{ $batch->item->name }} · {{ $batch->batch_number }} · {{ $batch->quantity }} · exp {{ $batch->expiry_date?->format('d M Y') ?? 'none' }}</li>
                    @empty
                        <li class="text-slate-500">Empty</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
    @if (auth()->user()->permits('inventory.purchase'))
        <form method="POST" action="{{ route('inventory.order') }}" class="mt-6 grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-3">
            @csrf
            <h2 class="font-semibold md:col-span-3">Purchase order to the main store</h2>
            <input name="supplier_name" placeholder="New supplier name" class="rounded-md border-slate-300 text-sm">
            <select name="supplier_id" class="rounded-md border-slate-300 text-sm">
                <option value="">Or choose a supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
            <select name="item_id" class="rounded-md border-slate-300 text-sm">@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <input name="quantity" type="number" min="1" required placeholder="Quantity" class="rounded-md border-slate-300 text-sm">
            <input name="unit_cost" type="number" min="0" required placeholder="Unit cost" class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Send order</x-primary-button>
        </form>
        @foreach ($orders as $order)
            <section class="mt-4 rounded-lg bg-white p-4 text-sm shadow-sm">
                <p class="font-medium">{{ $order->number }} · {{ $order->supplier->name }} · {{ $order->status }}</p>
                @foreach ($order->lines as $line)
                    <form method="POST" action="{{ route('inventory.receive', $order) }}" class="mt-2 grid gap-2 md:grid-cols-5">
                        @csrf
                        <input type="hidden" name="purchase_order_line_id" value="{{ $line->id }}">
                        <span>{{ $line->item->name }} {{ $line->received_quantity }}/{{ $line->quantity }}</span>
                        <input name="batch_number" placeholder="Batch" class="rounded-md border-slate-300 text-sm">
                        <input type="date" name="expiry_date" class="rounded-md border-slate-300 text-sm">
                        <input name="quantity" type="number" min="1" placeholder="Qty" class="rounded-md border-slate-300 text-sm">
                        <button class="text-brand-700">Receive</button>
                    </form>
                @endforeach
            </section>
        @endforeach
    @endif
    @if (auth()->user()->permits('inventory.requisition'))
        <form method="POST" action="{{ route('inventory.requisition') }}" class="mt-6 grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4">
            @csrf
            <h2 class="font-semibold md:col-span-4">Requisition from the main store</h2>
            <select name="stock_location_id" class="rounded-md border-slate-300 text-sm">@foreach ($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select>
            <select name="item_id" class="rounded-md border-slate-300 text-sm">@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <input name="quantity" type="number" min="1" required class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Request</x-primary-button>
        </form>
    @endif
    @foreach ($requisitions as $requisition)
        <div class="mt-3 flex items-center justify-between rounded-lg bg-white p-4 text-sm shadow-sm">
            <span>{{ $requisition->number }} → {{ $requisition->location->name }} · {{ $requisition->status }}</span>
            @if ($requisition->status === 'submitted' && auth()->user()->permits('inventory.purchase'))
                <form method="POST" action="{{ route('inventory.issue', $requisition) }}">@csrf<button class="text-brand-700">Issue</button></form>
            @endif
        </div>
    @endforeach
    @if (auth()->user()->permits('inventory.adjust'))
        <form method="POST" action="{{ route('inventory.adjust') }}" class="mt-6 grid gap-2 rounded-lg bg-white p-5 shadow-sm md:grid-cols-3">
            @csrf
            <h2 class="font-semibold md:col-span-3">Stock adjustment (needs approval)</h2>
            <select name="stock_location_id" class="rounded-md border-slate-300 text-sm">@foreach ($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select>
            <select name="item_id" class="rounded-md border-slate-300 text-sm">@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <input name="quantity" type="number" required placeholder="Signed quantity" class="rounded-md border-slate-300 text-sm">
            <select name="reason_code" class="rounded-md border-slate-300 text-sm">
                <option value="expiry">Expiry</option>
                <option value="damage">Damage</option>
                <option value="loss">Loss</option>
                <option value="count">Count variance</option>
            </select>
            <input name="notes" placeholder="Notes" class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Request adjustment</x-primary-button>
        </form>
    @endif
    @foreach ($adjustments as $adjustment)
        <div class="mt-3 flex items-center justify-between rounded-lg bg-white p-4 text-sm shadow-sm">
            <span>{{ $adjustment->item->name }} · {{ $adjustment->quantity }} · {{ $adjustment->reason_code }} · {{ $adjustment->status }}</span>
            @if ($adjustment->status === 'pending' && auth()->user()->permits('inventory.approve'))
                <form method="POST" action="{{ route('inventory.approve', $adjustment) }}">@csrf<button class="text-brand-700">Approve</button></form>
            @endif
        </div>
    @endforeach
</x-app-layout>
