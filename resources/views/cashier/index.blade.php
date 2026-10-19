<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-wallet">Cashier</x-page-title></x-slot>
    <section class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        @if ($shift)
            <p class="text-sm">Shift open since {{ $shift->opened_at->format('d M H:i') }} with float {{ \App\Support\Money::format($shift->opening_float) }}.</p>
            <form method="POST" action="{{ route('cashier.close', $shift) }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="text-sm" for="cash_counted">Cash counted</label>
                    <input id="cash_counted" name="cash_counted" type="number" min="0" required class="mt-1 block rounded-md border-slate-300 text-sm">
                </div>
                <x-primary-button>Close shift</x-primary-button>
            </form>
        @else
            <form method="POST" action="{{ route('cashier.shift') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="text-sm" for="opening_float">Opening float</label>
                    <input id="opening_float" name="opening_float" type="number" min="0" value="0" required class="mt-1 block rounded-md border-slate-300 text-sm">
                </div>
                <x-primary-button>Open shift</x-primary-button>
            </form>
        @endif
    </section>
    <div class="grid gap-4">
        @foreach ($bills as $bill)
            <section class="rounded-lg bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-medium">{{ $bill->patient->fullName() }} · {{ $bill->visit->visit_number }}</p>
                        <p class="text-sm text-slate-500">Balance {{ \App\Support\Money::format($bill->cashBalance()) }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('cashier.pay', $bill) }}" class="mt-3 grid gap-2 md:grid-cols-4">
                    @csrf
                    <select name="method" class="rounded-md border-slate-300 text-sm">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    <input name="amount" type="number" min="1" value="{{ $bill->cashBalance() }}" required class="rounded-md border-slate-300 text-sm">
                    <input name="reference" placeholder="MoMo or card reference" class="rounded-md border-slate-300 text-sm">
                    <x-primary-button>Take payment</x-primary-button>
                </form>
                <form method="POST" action="{{ route('cashier.waivers', $bill) }}" class="mt-2 grid gap-2 md:grid-cols-3">
                    @csrf
                    <input name="amount" type="number" min="1" placeholder="Waiver amount" class="rounded-md border-slate-300 text-sm">
                    <input name="reason" placeholder="Reason" class="rounded-md border-slate-300 text-sm">
                    <button class="text-sm text-slate-700">Request waiver</button>
                </form>
            </section>
        @endforeach
    </div>
    @if (auth()->user()->permits('waivers.approve') && $waivers->isNotEmpty())
        <section class="mt-6 rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Pending waivers</h2>
            @foreach ($waivers as $waiver)
                <form method="POST" action="{{ route('waivers.approve', $waiver) }}" class="mt-3 flex items-center justify-between text-sm">
                    @csrf
                    <span>{{ $waiver->bill->patient->fullName() }} · {{ \App\Support\Money::format($waiver->amount) }} · {{ $waiver->reason }}</span>
                    <x-primary-button>Approve</x-primary-button>
                </form>
            @endforeach
        </section>
    @endif
</x-app-layout>
