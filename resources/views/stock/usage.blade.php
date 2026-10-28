@php
    $select = 'mt-1 block w-full rounded-md border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm';
    $groupLabels = ['department' => 'Department', 'employee' => 'Employee', 'item' => 'Item'];
    $query = array_filter([
        'start' => $start,
        'end' => $end,
        'group_by' => $groupBy,
        ...$filters,
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-title icon="bx-line-chart-down">Stock usage</x-page-title>
            <a class="inline-flex items-center gap-1 text-sm text-brand-700" href="{{ route('stock.usage.export', $query) }}">
                <x-icon name="bx-export" class="text-lg" />
                Export CSV
            </a>
        </div>
    </x-slot>

    <form method="GET" class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-card sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <x-input-label for="usage-start" value="From" />
            <x-text-input id="usage-start" type="date" name="start" :value="$start" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="usage-end" value="To" />
            <x-text-input id="usage-end" type="date" name="end" :value="$end" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('end')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="usage-group" value="Group by" />
            <select id="usage-group" name="group_by" class="{{ $select }}">
                @foreach ($groupLabels as $value => $label)
                    <option value="{{ $value }}" @selected($groupBy === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="usage-reason" value="Kind of use" />
            <select id="usage-reason" name="reason" class="{{ $select }}">
                <option value="">All use (dispensing, ward, department, lab)</option>
                @foreach ($reasons as $reason)
                    <option value="{{ $reason->value }}" @selected($filters['reason'] === $reason->value)>{{ $reason->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="usage-department" value="Department" />
            <select id="usage-department" name="department_id" class="{{ $select }}">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="usage-employee" value="Employee" />
            <select id="usage-employee" name="performed_by" class="{{ $select }}">
                <option value="">All employees</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($filters['performed_by'] === $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="usage-item" value="Item" />
            <select id="usage-item" name="item_id" class="{{ $select }}">
                <option value="">All items</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected($filters['item_id'] === $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end"><x-primary-button>Update</x-primary-button></div>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Value used, at cost</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ \App\Support\Money::format($rows->sum('value')) }}</p>
        </section>
        <section class="rounded-lg bg-white p-5 shadow-card">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Different items used</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $rows->pluck('item')->unique()->count() }}</p>
        </section>
    </div>

    @forelse ($groups as $group => $lines)
        <section class="mb-4 rounded-lg bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-bold text-slate-900">{{ $group }}</h2>
                <span class="text-sm text-slate-500">{{ \App\Support\Money::format($lines->sum('value')) }} at cost</span>
            </div>
            @if ($groupBy !== 'item')
                <table class="mt-3 min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-2 pe-4 font-medium">Item</th>
                            <th class="py-2 pe-4 text-right font-medium">Quantity used</th>
                            <th class="py-2 text-right font-medium">Value at cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr class="border-t border-slate-100">
                                <td class="py-2 pe-4">{{ $line['item'] }}</td>
                                <td class="py-2 pe-4 text-right tabular-nums">{{ number_format($line['quantity']) }} {{ \Illuminate\Support\Str::plural($line['unit'], $line['quantity']) }}</td>
                                <td class="py-2 text-right tabular-nums">{{ \App\Support\Money::format($line['value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="mt-1 text-sm">{{ number_format($lines->sum('quantity')) }} {{ \Illuminate\Support\Str::plural($lines->first()['unit'], $lines->sum('quantity')) }} used</p>
            @endif
        </section>
    @empty
        <section class="rounded-lg bg-white p-5 text-sm text-slate-500 shadow-card">No stock was used in this period with these filters.</section>
    @endforelse
</x-app-layout>
