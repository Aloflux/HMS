<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('wards.index') }}" class="text-sm text-brand-700">Wards</a>
                <x-page-title>{{ $admission->patient->fullName() }}</x-page-title>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $admission->bed->ward->name }} {{ $admission->bed->code }} · admitted {{ $admission->admitted_at->format('d M Y H:i') }}
                    · <a class="text-brand-700" href="{{ route('visits.show', $admission->visit) }}">Visit {{ $admission->visit->visit_number }}</a>
                </p>
            </div>
            <x-status :value="$admission->status" />
        </div>
    </x-slot>

    @unless ($admission->isOpen())
        <div class="mb-6 rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Discharged {{ $admission->discharged_at?->format('d M Y H:i') }} · {{ ucfirst((string) $admission->discharge_outcome) }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $admission->discharge_summary }}</p>
            <p class="mt-2 text-xs text-slate-500">The ward record is closed. Nothing more can be charted or issued.</p>
        </div>
    @endunless

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Nursing notes</h2>
            @if ($admission->isOpen() && auth()->user()->permits('wards.manage'))
                <form method="POST" action="{{ route('wards.notes', $admission) }}" class="mt-3">
                    @csrf
                    <x-input-label for="note" value="New note" />
                    <textarea id="note" name="note" rows="3" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">{{ old('note') }}</textarea>
                    <div class="mt-3"><x-primary-button>Save note</x-primary-button></div>
                </form>
            @endif
            <ul class="mt-4 divide-y divide-slate-100 text-sm">
                @forelse ($admission->nursingNotes->sortByDesc('created_at') as $note)
                    <li class="py-2">
                        <span class="block text-xs text-slate-500">{{ $note->created_at->format('d M H:i') }} · {{ $note->recorder?->name }}</span>
                        <span class="block whitespace-pre-line">{{ $note->note }}</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-500">No notes yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Medication chart</h2>
            @if ($admission->isOpen() && auth()->user()->permits('wards.manage'))
                <form method="POST" action="{{ route('wards.medications', $admission) }}" class="mt-3 grid gap-3 sm:grid-cols-3">
                    @csrf
                    <div class="sm:col-span-3">
                        <x-input-label for="medication-item" value="Medicine" />
                        <x-search-select id="medication-item" name="item_id" :options="$items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->unit])" :selected="old('item_id')" placeholder="Search medicines" required />
                    </div>
                    <div>
                        <x-input-label for="medication-dose" value="Dose" />
                        <x-text-input id="medication-dose" name="dose" class="mt-1 block w-full" :value="old('dose')" placeholder="500 mg" required />
                    </div>
                    <div>
                        <x-input-label for="medication-status" value="Status" />
                        <select id="medication-status" name="status" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                            @foreach (['given' => 'Given', 'due' => 'Due', 'missed' => 'Missed', 'refused' => 'Refused'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end"><x-primary-button>Chart dose</x-primary-button></div>
                    <p class="text-xs text-slate-500 sm:col-span-3">Charting a dose does not move stock. Stock already left at dispense.</p>
                </form>
            @endif
            <ul class="mt-4 divide-y divide-slate-100 text-sm">
                @forelse ($admission->administrations->sortByDesc('created_at') as $dose)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <span>
                            <span class="block font-medium">{{ $dose->item?->name }} · {{ $dose->dose }}</span>
                            <span class="block text-xs text-slate-500">{{ $dose->created_at->format('d M H:i') }}</span>
                        </span>
                        <x-status :value="$dose->status" />
                    </li>
                @empty
                    <li class="py-2 text-slate-500">Nothing charted yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Ward stock used</h2>
            @if ($admission->isOpen() && auth()->user()->permits('wards.manage'))
                @if ($locations->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">This facility has no ward store yet. Ask the stores team to set one up and issue stock to it.</p>
                @else
                    <form method="POST" action="{{ route('wards.issue', $admission) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <div>
                            <x-input-label for="issue-location" value="From store" />
                            <select id="issue-location" name="stock_location_id" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}" @selected((string) old('stock_location_id') === (string) $location->id)>{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="issue-quantity" value="Quantity" />
                            <x-text-input id="issue-quantity" name="quantity" type="number" min="1" :value="old('quantity', 1)" class="mt-1 block w-full" required />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="issue-item" value="Item" />
                            <x-search-select id="issue-item" name="item_id" :options="$items->map(fn ($item) => ['value' => $item->id, 'label' => $item->name, 'hint' => $item->billing_type->label()])" :selected="old('item_id')" placeholder="Search items" required />
                        </div>
                        <div class="sm:col-span-2"><x-primary-button>Issue</x-primary-button></div>
                    </form>
                @endif
            @endif
            <ul class="mt-4 divide-y divide-slate-100 text-sm">
                @forelse ($admission->consumptions->sortByDesc('created_at') as $consumption)
                    <li class="flex justify-between gap-3 py-2">
                        <span>{{ $consumption->item?->name }} × {{ $consumption->quantity }}</span>
                        <span class="text-xs text-slate-500">{{ $consumption->created_at->format('d M H:i') }} · {{ $consumption->bill_line_id ? 'Charged to patient' : 'Department cost' }}</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-500">No ward stock used yet.</li>
                @endforelse
            </ul>
        </section>

        @if ($admission->isOpen() && auth()->user()->permits('wards.manage'))
            <form method="POST" action="{{ route('wards.discharge', $admission) }}" class="rounded-lg bg-white p-5 shadow-card">
                @csrf
                <h2 class="font-bold text-slate-900">Discharge</h2>
                <p class="mt-1 text-sm text-slate-500">The cash balance must be settled and pending prescriptions and lab tests closed first.</p>
                <div class="mt-3">
                    <x-input-label for="discharge-summary" value="Discharge summary" />
                    <textarea id="discharge-summary" name="discharge_summary" rows="3" required class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">{{ old('discharge_summary') }}</textarea>
                </div>
                <div class="mt-3">
                    <x-input-label for="discharge-outcome" value="Outcome" />
                    <select id="discharge-outcome" name="discharge_outcome" class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm">
                        @foreach (['recovered' => 'Recovered', 'referred' => 'Referred', 'died' => 'Died', 'absconded' => 'Absconded'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('discharge_outcome') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3"><x-primary-button>Discharge</x-primary-button></div>
            </form>
        @endif
    </div>
</x-app-layout>
