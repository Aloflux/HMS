<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <x-page-title icon="bx-receipt">Receipt</x-page-title>
            <button type="button" class="no-print inline-flex items-center gap-1 text-sm text-brand-700" data-print>
                <x-icon name="bx-printer" class="text-lg" />
                Print
            </button>
        </div>
    </x-slot>
    <article class="print-sheet mx-auto max-w-md rounded-2xl bg-white p-6 shadow-card">
        <h2 class="text-lg font-semibold">{{ $payment->bill->visit->facility->name ?? auth()->user()->facility->name }}</h2>
        <p class="text-sm text-slate-500">{{ $payment->created_at->format('d M Y H:i') }}</p>
        <p class="mt-4 text-sm">{{ $payment->bill->patient->fullName() }}</p>
        <p class="text-sm">{{ $payment->bill->visit->visit_number }}</p>
        <ul class="mt-4 divide-y divide-slate-100 text-sm">
            @foreach ($payment->bill->lines as $line)
                <li class="flex justify-between py-2"><span>{{ $line->description }}</span><span>{{ \App\Support\Money::format($line->amount) }}</span></li>
            @endforeach
        </ul>
        <p class="mt-4 font-semibold">{{ $payment->method->label() }} {{ \App\Support\Money::format($payment->amount) }}</p>
        @if ($payment->reference)
            <p class="text-sm">Reference {{ $payment->reference }}</p>
        @endif
        <p class="mt-2 text-sm">Balance {{ \App\Support\Money::format($payment->bill->cashBalance()) }}</p>
    </article>
</x-app-layout>
