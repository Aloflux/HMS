@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border border-slate-300 shadow-none focus:border-brand-600 focus:ring-0 focus:outline-none']) }}>
