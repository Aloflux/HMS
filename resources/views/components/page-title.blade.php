@props(['icon'])

<h1 {{ $attributes->class('flex items-center gap-2 text-2xl font-semibold text-slate-900') }}>
    <x-icon :name="$icon" class="text-brand-700" />
    <span>{{ $slot }}</span>
</h1>
