<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-capsule">Pharmacy</x-page-title></x-slot>
    <section class="rounded-lg bg-white p-5 shadow-sm">
        <h2 class="font-semibold">Pharmacy shelf</h2>
        <p class="mt-1 text-sm text-slate-500">Working stock in the pharmacy. The batch that expires sooner is used first. The rest stays in the main store until it is issued.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-2 pe-4 font-medium">Medicine</th>
                        <th class="py-2 pe-4 font-medium">Batch</th>
                        <th class="py-2 pe-4 font-medium">Expiry</th>
                        <th class="py-2 pe-4 font-medium">On hand</th>
                        <th class="py-2 pe-4 font-medium">Unit price</th>
                        <th class="py-2 font-medium">Reorder level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shelf as $batch)
                        <tr class="border-t border-slate-100">
                            <td class="py-2 pe-4 font-medium">{{ $batch->item->name }}</td>
                            <td class="py-2 pe-4">{{ $batch->batch_number }}</td>
                            <td class="py-2 pe-4">{{ $batch->expiry_date?->format('d M Y') ?? 'No expiry' }}</td>
                            <td class="py-2 pe-4">{{ $batch->quantity }} {{ \Illuminate\Support\Str::plural($batch->item->unit, $batch->quantity) }}</td>
                            <td class="py-2 pe-4">{{ \App\Support\Money::format($batch->item->cash_price) }}</td>
                            <td class="py-2">{{ $batch->item->reorder_level }} {{ \Illuminate\Support\Str::plural($batch->item->unit, $batch->item->reorder_level) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-3 text-slate-500">The pharmacy shelf is empty. Issue stock from the main store.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4 grid gap-4">
        @forelse ($items as $line)
            @php
                $dispensed = (int) $line->dispensations->sum('quantity');
                $remaining = max(0, $line->quantity - $dispensed);
                $unit = $line->item->unit;
                $shelfQty = (int) ($onHand[$line->item_id] ?? 0);
            @endphp
            <article class="rounded-lg bg-white p-5 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medicine</p>
                        <p class="mt-1 text-lg font-semibold">{{ $line->item->name }}</p>
                        <p class="text-sm text-slate-500">{{ $unit }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Patient</p>
                        <p class="mt-1 font-medium">
                            <a class="text-brand-700" href="{{ route('visits.show', $line->prescription->visit) }}">{{ $line->prescription->visit->patient->fullName() }}</a>
                        </p>
                        <p class="text-sm text-slate-500">Visit {{ $line->prescription->visit->visit_number }}</p>
                    </div>
                </div>

                <dl class="mt-4 grid gap-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <dt class="text-slate-500">Dose</dt>
                        <dd class="font-medium">{{ $line->dose }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Frequency</dt>
                        <dd class="font-medium">{{ $line->frequency }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Duration</dt>
                        <dd class="font-medium">{{ $line->duration_days }} days</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Prescribed</dt>
                        <dd class="font-medium">{{ $line->quantity }} {{ \Illuminate\Support\Str::plural($unit, $line->quantity) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Already dispensed</dt>
                        <dd class="font-medium">{{ $dispensed }} {{ \Illuminate\Support\Str::plural($unit, $dispensed) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Still to dispense</dt>
                        <dd class="font-medium">{{ $remaining }} {{ \Illuminate\Support\Str::plural($unit, $remaining) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">On the pharmacy shelf</dt>
                        <dd class="font-medium">{{ $shelfQty }} {{ \Illuminate\Support\Str::plural($unit, $shelfQty) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Unit price</dt>
                        <dd class="font-medium">{{ \App\Support\Money::format($line->item->cash_price) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Status</dt>
                        <dd class="font-medium">{{ $remaining > 0 ? 'Waiting to dispense' : 'Dispensed, waiting for collection' }}</dd>
                    </div>
                </dl>

                @if ($remaining > 0 && auth()->user()->permits('pharmacy.manage'))
                    <form method="POST" action="{{ route('pharmacy.dispense', $line) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-4 md:items-end">
                        @csrf
                        <div>
                            <x-input-label :for="'quantity-'.$line->id" value="Quantity to dispense" />
                            <x-text-input :id="'quantity-'.$line->id" type="number" name="quantity" min="1" :max="$remaining" :value="$remaining" class="mt-1 block w-full" required />
                            <p class="mt-1 text-xs text-slate-500">In {{ $unit }}. Up to {{ $remaining }} still on this prescription.</p>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label :for="'substitute-'.$line->id" value="Substitute medicine" />
                            <select id="substitute-{{ $line->id }}" name="substitute_item_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600">
                                <option value="">Dispense {{ $line->item->name }}</option>
                                @foreach ($substitutes as $substitute)
                                    @continue($substitute->id === $line->item_id)
                                    <option value="{{ $substitute->id }}">{{ $substitute->name }} · {{ $substitute->unit }} · {{ \App\Support\Money::format($substitute->cash_price) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-primary-button>Dispense</x-primary-button>
                            <button form="cancel-{{ $line->id }}" class="text-sm text-red-700">Cancel line</button>
                        </div>
                    </form>
                    <form id="cancel-{{ $line->id }}" method="POST" action="{{ route('pharmacy.cancel', $line) }}">@csrf</form>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">No prescriptions are waiting to dispense or collect.</p>
        @endforelse
    </div>
</x-app-layout>
