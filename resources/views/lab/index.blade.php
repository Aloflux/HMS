<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-test-tube">Laboratory</x-page-title></x-slot>
    <div class="grid gap-4">
        @forelse ($items as $item)
            <form method="POST" action="{{ route('lab.verify', $item) }}" class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4 md:items-end">
                @csrf
                <div class="md:col-span-2">
                    <p class="font-medium">{{ $item->test->name }}</p>
                    <p class="text-sm text-slate-500">{{ $item->request->visit->patient->fullName() }} · {{ $item->request->visit->visit_number }} · {{ $item->test->reference_text }}</p>
                </div>
                @if (auth()->user()->permits('lab.manage'))
                    <input name="result_value" required placeholder="Result" class="rounded-md border-slate-300 text-sm">
                    <x-primary-button>Verify</x-primary-button>
                @endif
            </form>
        @empty
            <p class="text-sm text-slate-500">No samples are waiting.</p>
        @endforelse
    </div>
</x-app-layout>
