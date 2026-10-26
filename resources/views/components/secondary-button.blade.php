<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card transition hover:bg-slate-50 hover:shadow-card-hover focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
