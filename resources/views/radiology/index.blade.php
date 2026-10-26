<x-app-layout>
    <x-slot name="header"><x-page-title>Radiology</x-page-title></x-slot>
    <div class="grid gap-4">
        @forelse ($orders as $order)
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $order->study->name }}</p>
                        <p class="text-sm text-slate-500">
                            <a href="{{ route('visits.show', $order->visit) }}" class="text-brand-700">{{ $order->visit->patient->fullName() }}</a>
                            · {{ $order->visit->visit_number }} · ordered {{ $order->created_at->format('d M H:i') }}
                        </p>
                        @if ($order->clinical_notes)
                            <p class="mt-1 text-sm text-slate-600">{{ $order->clinical_notes }}</p>
                        @endif
                    </div>
                    <x-status :value="$order->status" />
                </div>
                @if ($order->report)
                    <div class="mt-3 border-t border-slate-100 pt-3 text-sm">
                        <p class="whitespace-pre-line">{{ $order->report }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $order->reporter?->name }} · {{ $order->reported_at?->format('d M Y H:i') }}</p>
                        @if ($order->image_path)
                            <a href="{{ route('radiology.image', $order) }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-brand-700">
                                <x-icon name="bx-image" /> View image
                            </a>
                        @endif
                    </div>
                @endif
                @if ($order->status === 'ordered' && auth()->user()->permits('radiology.manage'))
                    <form method="POST" action="{{ route('radiology.report', $order) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 border-t border-slate-100 pt-4">
                        @csrf
                        <div>
                            <x-input-label :for="'report-'.$order->id" value="Report" />
                            <textarea id="report-{{ $order->id }}" name="report" rows="3" required class="mt-1 block w-full rounded-xl border-transparent bg-slate-100/70 focus:border-brand-600 focus:bg-white focus:ring-0 text-sm"></textarea>
                        </div>
                        <div>
                            <x-input-label :for="'image-'.$order->id" value="Image or PDF (optional, up to 5 MB)" />
                            <input id="image-{{ $order->id }}" type="file" name="image" accept="image/jpeg,image/png,.pdf" class="mt-1 text-sm">
                        </div>
                        <div><x-primary-button>Save report</x-primary-button></div>
                    </form>
                @endif
            </section>
        @empty
            <p class="text-sm text-slate-500">No imaging orders yet.</p>
        @endforelse
    </div>
</x-app-layout>
