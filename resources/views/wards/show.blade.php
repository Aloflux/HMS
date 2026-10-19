<x-app-layout>
    <x-slot name="header">
        <x-page-title icon="bx-bed">{{ $admission->patient->fullName() }}</x-page-title>
        <p class="text-sm text-slate-500">{{ $admission->bed->ward->name }} {{ $admission->bed->code }} · admitted {{ $admission->admitted_at->format('d M Y H:i') }}</p>
    </x-slot>
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('wards.notes', $admission) }}" class="rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Nursing note</h2>
            <textarea name="note" required class="mt-3 w-full rounded-md border-slate-300 text-sm"></textarea>
            <div class="mt-3"><x-primary-button>Save note</x-primary-button></div>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($admission->nursingNotes as $note)
                    <li>{{ $note->created_at->format('d M H:i') }} · {{ $note->note }}</li>
                @endforeach
            </ul>
        </form>
        <form method="POST" action="{{ route('wards.medications', $admission) }}" class="rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Medication chart</h2>
            <p class="mt-1 text-xs text-slate-500">Marking a dose given does not move stock. Stock already left at dispense.</p>
            <select name="item_id" class="mt-3 w-full rounded-md border-slate-300 text-sm">
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
            <input name="dose" required placeholder="Dose" class="mt-2 w-full rounded-md border-slate-300 text-sm">
            <select name="status" class="mt-2 w-full rounded-md border-slate-300 text-sm">
                <option value="given">Given</option>
                <option value="due">Due</option>
                <option value="missed">Missed</option>
                <option value="refused">Refused</option>
            </select>
            <div class="mt-3"><x-primary-button>Chart dose</x-primary-button></div>
        </form>
        <form method="POST" action="{{ route('wards.issue', $admission) }}" class="rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Issue from ward stock</h2>
            <select name="stock_location_id" class="mt-3 w-full rounded-md border-slate-300 text-sm">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <select name="item_id" class="mt-2 w-full rounded-md border-slate-300 text-sm">
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">{{ $item->name }} · {{ $item->billing_type->label() }}</option>
                @endforeach
            </select>
            <input name="quantity" type="number" min="1" value="1" class="mt-2 w-full rounded-md border-slate-300 text-sm">
            <div class="mt-3"><x-primary-button>Issue</x-primary-button></div>
        </form>
        <form method="POST" action="{{ route('wards.discharge', $admission) }}" class="rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Discharge</h2>
            <textarea name="discharge_summary" required class="mt-3 w-full rounded-md border-slate-300 text-sm"></textarea>
            <select name="discharge_outcome" class="mt-2 w-full rounded-md border-slate-300 text-sm">
                <option value="recovered">Recovered</option>
                <option value="referred">Referred</option>
                <option value="died">Died</option>
                <option value="absconded">Absconded</option>
            </select>
            <div class="mt-3"><x-primary-button>Discharge</x-primary-button></div>
        </form>
    </div>
</x-app-layout>
