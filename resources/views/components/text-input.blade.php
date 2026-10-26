@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-transparent bg-slate-100/70 shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none']) }}>
