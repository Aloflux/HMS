<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-scan">Radiology</x-page-title></x-slot>
    <div class="grid gap-4">
        @foreach ($orders as $order)
            <section class="rounded-lg bg-white p-4 shadow-sm">
                <p class="font-medium">{{ $order->study->name }} · {{ $order->status }}</p>
                <p class="text-sm text-slate-500">{{ $order->visit->patient->fullName() }}</p>
                @if ($order->report)
                    <p class="mt-2 text-sm">{{ $order->report }}</p>
                @endif
                @if ($order->status === 'ordered' && auth()->user()->permits('radiology.manage'))
                    <form method="POST" action="{{ route('radiology.report', $order) }}" enctype="multipart/form-data" class="mt-3 grid gap-2">
                        @csrf
                        <textarea name="report" required placeholder="Report" class="rounded-md border-slate-300 text-sm"></textarea>
                        <input type="file" name="image" accept="image/*,.pdf" class="text-sm">
                        <x-primary-button>Save report</x-primary-button>
                    </form>
                @endif
            </section>
        @endforeach
    </div>
</x-app-layout>
