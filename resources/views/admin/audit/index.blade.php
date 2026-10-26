<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-history">Audit trail</x-page-title></x-slot>
    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @foreach ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d M H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3">{{ $log->action }}</td>
                        <td class="px-4 py-3">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
