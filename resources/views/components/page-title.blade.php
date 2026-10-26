@props(['icon' => null])

<h1 {{ $attributes->class('text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl') }}>
    {{ $slot }}
</h1>
