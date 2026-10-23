<x-app-layout>
    <x-slot name="header"><x-page-title>Cashier</x-page-title></x-slot>

    <section class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        @if ($shift)
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="font-semibold">Shift open</h2>
                    <p class="mt-1 text-sm text-slate-500">Since {{ $shift->opened_at->format('d M H:i') }} with a float of {{ \App\Support\Money::format($shift->opening_float) }}.</p>
                </div>
                <form method="POST" action="{{ route('cashier.close', $shift) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="cash_counted" value="Cash counted in the drawer (UGX)" />
                        <x-text-input id="cash_counted" name="cash_counted" type="number" min="0" class="mt-1 block" required />
                    </div>
                    <x-secondary-button type="submit">Close shift</x-secondary-button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('cashier.shift') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <h2 class="font-semibold">Open a shift to take payments</h2>
                    <x-input-label for="opening_float" value="Opening float (UGX)" class="mt-2" />
                    <x-text-input id="opening_float" name="opening_float" type="number" min="0" value="0" class="mt-1 block" required />
                </div>
                <x-primary-button>Open shift</x-primary-button>
            </form>
        @endif
    </section>

    <h2 class="mb-3 font-semibold">Bills waiting for payment</h2>
    <div class="grid gap-4">
        @forelse ($bills as $bill)
            @php($balance = $bill->cashBalance())
            <section class="rounded-lg bg-white p-5 shadow-sm" x-data="{ method: 'cash', waiver: false }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('visits.show', $bill->visit) }}" class="font-medium text-slate-900">{{ $bill->patient->fullName() }}</a>
                        <p class="text-sm text-slate-500">Visit {{ $bill->visit->visit_number }} · {{ $bill->lines->count() }} {{ \Illuminate\Support\Str::plural('charge', $bill->lines->count()) }}</p>
                    </div>
                    <div class="text-end">
                        <span class="block text-xs text-slate-500">Balance</span>
                        <span class="text-lg font-semibold">{{ \App\Support\Money::format($balance) }}</span>
                    </div>
                </div>

                @if ($shift)
                    <form method="POST" action="{{ route('cashier.pay', $bill) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-4 md:items-end">
                        @csrf
                        <div>
                            <x-input-label :for="'method-'.$bill->id" value="Method" />
                            <select id="method-{{ $bill->id }}" name="method" x-model="method" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                                @foreach ($methods as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label :for="'amount-'.$bill->id" value="Amount (UGX)" />
                            <x-text-input :id="'amount-'.$bill->id" name="amount" type="number" min="1" :max="$balance" :value="$balance" class="mt-1 block w-full" required />
                        </div>
                        <div x-show="method !== 'cash'" x-cloak>
                            <x-input-label :for="'reference-'.$bill->id" value="Mobile money or card reference" />
                            <x-text-input :id="'reference-'.$bill->id" name="reference" class="mt-1 block w-full" x-bind:required="method !== 'cash'" />
                        </div>
                        <div><x-primary-button>Take payment</x-primary-button></div>
                    </form>
                @else
                    <p class="mt-3 text-sm text-slate-500">Open a shift above to take payment.</p>
                @endif

                <div class="mt-3">
                    <button type="button" class="text-sm text-slate-600 hover:text-slate-900" @click="waiver = ! waiver" x-text="waiver ? 'Hide waiver request' : 'Patient cannot pay? Request a waiver'"></button>
                    <form method="POST" action="{{ route('cashier.waivers', $bill) }}" class="mt-3 grid gap-3 md:grid-cols-4 md:items-end" x-show="waiver" x-cloak>
                        @csrf
                        <div>
                            <x-input-label :for="'waiver-amount-'.$bill->id" value="Amount to waive (UGX)" />
                            <x-text-input :id="'waiver-amount-'.$bill->id" name="amount" type="number" min="1" :max="$balance" class="mt-1 block w-full" required />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label :for="'waiver-reason-'.$bill->id" value="Reason" />
                            <x-text-input :id="'waiver-reason-'.$bill->id" name="reason" class="mt-1 block w-full" required />
                        </div>
                        <div><x-secondary-button type="submit">Send for approval</x-secondary-button></div>
                    </form>
                </div>
            </section>
        @empty
            <p class="rounded-lg bg-white p-5 text-sm text-slate-500 shadow-sm">No cash bills are waiting.</p>
        @endforelse
    </div>

    @if ($waivers->isNotEmpty())
        <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Waivers</h2>
            <p class="mt-1 text-sm text-slate-500">Pending requests, and decisions from the last day.</p>
            <ul class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ($waivers as $waiver)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <span>
                            <span class="block font-medium">{{ $waiver->bill->patient->fullName() }} · {{ \App\Support\Money::format($waiver->amount) }}</span>
                            <span class="block text-xs text-slate-500">{{ $waiver->reason }} · asked by {{ $waiver->requester?->name ?? 'unknown' }}{{ $waiver->decider ? ' · decided by '.$waiver->decider->name : '' }}</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <x-status :value="$waiver->status" />
                            @if ($waiver->status === 'pending' && auth()->user()->permits('waivers.approve'))
                                <form method="POST" action="{{ route('waivers.reject', $waiver) }}">@csrf<button class="text-sm text-red-700">Reject</button></form>
                                <form method="POST" action="{{ route('waivers.approve', $waiver) }}">@csrf<x-primary-button>Approve</x-primary-button></form>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
