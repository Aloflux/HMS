@props(['icon' => null])

<h1 {{ $attributes->class('text-2xl font-semibold tracking-tight text-slate-900') }}>
    {{ $slot }}
</h1>
