<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-list-ol">Live queues</x-page-title></x-slot>
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('queues.index') }}" class="rounded-full bg-white px-3 py-1 text-sm">All</a>
        @foreach ($stages as $stageOption)
            <a href="{{ route('queues.index', ['stage' => $stageOption->value]) }}" class="rounded-full px-3 py-1 text-sm {{ $stage === $stageOption->value ? 'bg-brand-700 text-white' : 'bg-white' }}">{{ $stageOption->label() }}</a>
        @endforeach
    </div>
    @if ($stage)
        <form method="POST" action="{{ route('queues.call') }}" class="mb-4">
            @csrf
            <input type="hidden" name="stage" value="{{ $stage }}">
            <x-primary-button>Call next</x-primary-button>
        </form>
    @endif
    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($entries as $entry)
                    <tr>
                        <td class="px-4 py-3">{{ $entry->stage->label() }}</td>
                        <td class="px-4 py-3">{{ $entry->priority->label() }}</td>
                        <td class="px-4 py-3"><a class="text-brand-700" href="{{ route('visits.show', $entry->visit) }}">{{ $entry->visit->patient->fullName() }}</a></td>
                        <td class="px-4 py-3">{{ $entry->status }}</td>
                        <td class="px-4 py-3">{{ $entry->queued_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-8 text-slate-500">The queue is empty.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
