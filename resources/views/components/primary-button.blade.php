<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-md bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-brand transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:bg-brand-900 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
