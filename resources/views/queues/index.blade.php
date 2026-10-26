<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-title>Live queues</x-page-title>
            <p class="mt-1 text-sm text-slate-500">Emergencies first, then urgent, then by arrival time.</p>
        </div>
    </x-slot>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('queues.index') }}" class="rounded-full px-3 py-1 text-sm {{ $stage === null ? 'bg-brand-700 text-white' : 'bg-white text-slate-700' }}">All</a>
        @foreach ($stages as $stageOption)
            <a href="{{ route('queues.index', ['stage' => $stageOption->value]) }}" class="rounded-full px-3 py-1 text-sm {{ $stage === $stageOption ? 'bg-brand-700 text-white' : 'bg-white text-slate-700' }}">{{ $stageOption->label() }}</a>
        @endforeach
    </div>

    @if ($stage && auth()->user()->permits($stage->permission()))
        <form method="POST" action="{{ route('queues.call') }}" class="mb-4">
            @csrf
            <input type="hidden" name="stage" value="{{ $stage->value }}">
            <x-primary-button>Call next to {{ strtolower($stage->label()) }}</x-primary-button>
        </form>
    @elseif ($stage === null)
        <p class="mb-4 text-sm text-slate-500">Choose a queue above to call the next patient.</p>
    @endif

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-slate-400 bg-slate-50/80">
                <tr>
                    <th class="px-4 py-3 font-medium">Patient</th>
                    <th class="px-4 py-3 font-medium">Queue</th>
                    <th class="px-4 py-3 font-medium">Priority</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Waiting</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($entries as $entry)
                    <tr>
                        <td class="px-4 py-3">
                            <a class="font-medium text-brand-700" href="{{ route('visits.show', $entry->visit) }}">{{ $entry->visit->patient->fullName() }}</a>
                            <span class="block text-xs text-slate-500">{{ $entry->visit->visit_number }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $entry->stage->label() }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $entry->priority === \App\Enums\Priority::Emergency ? 'font-semibold text-red-700' : '' }}">{{ $entry->priority->label() }}</span>
                        </td>
                        <td class="px-4 py-3"><x-status :value="$entry->status" /></td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $entry->queued_at->diffForHumans(null, true) }}</td>
                        <td class="px-4 py-3 text-end">
                            @if ($entry->status === 'serving' && auth()->user()->permits($entry->stage->permission()))
                                <form method="POST" action="{{ route('queues.complete', $entry) }}">
                                    @csrf
                                    <button class="text-sm font-semibold text-brand-700">Done</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-slate-500">Nobody is waiting{{ $stage ? ' for '.strtolower($stage->label()) : '' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
