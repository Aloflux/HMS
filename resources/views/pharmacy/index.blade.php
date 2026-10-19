<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-capsule">Pharmacy</x-page-title></x-slot>
    <div class="grid gap-4">
        @forelse ($items as $line)
            <form method="POST" action="{{ route('pharmacy.dispense', $line) }}" class="grid gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4 md:items-end">
                @csrf
                <div class="md:col-span-4">
                    <p class="font-medium">{{ $line->item->name }} · {{ $line->dose }} {{ $line->frequency }}</p>
                    <p class="text-sm text-slate-500">{{ $line->prescription->visit->patient->fullName() }} · remaining {{ $line->remainingQuantity() }} · {{ \App\Support\Money::format($line->item->cash_price) }} each</p>
                </div>
                @if (auth()->user()->permits('pharmacy.manage'))
                    <input type="number" name="quantity" min="1" value="{{ $line->remainingQuantity() }}" class="rounded-md border-slate-300 text-sm">
                    <select name="substitute_item_id" class="rounded-md border-slate-300 text-sm">
                        <option value="">No substitution</option>
                        @foreach ($substitutes as $substitute)
                            <option value="{{ $substitute->id }}">{{ $substitute->name }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Dispense</x-primary-button>
                    <button form="cancel-{{ $line->id }}" class="text-sm text-red-700">Cancel line</button>
                @endif
            </form>
            <form id="cancel-{{ $line->id }}" method="POST" action="{{ route('pharmacy.cancel', $line) }}">@csrf</form>
        @empty
            <p class="text-sm text-slate-500">No prescriptions are waiting.</p>
        @endforelse
    </div>
</x-app-layout>
