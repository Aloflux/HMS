@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-brand-800']) }}>
        {{ $status }}
    </div>
@endif
