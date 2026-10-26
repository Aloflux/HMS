@props(['disabled' => false])

@php($fieldClasses = 'rounded-md border-transparent bg-slate-100/70 shadow-none focus:border-brand-600 focus:bg-white focus:ring-0 focus:outline-none')

@if ($attributes->get('type') === 'password')
    {{-- Layout classes (margins, width) go on the wrapper so the reveal button sits inside the field. --}}
    <div x-data="{ visible: false }" {{ $attributes->only('class')->class('relative') }}>
        <input @disabled($disabled) {{ $attributes->except('class')->merge(['class' => $fieldClasses.' block w-full pe-11']) }} :type="visible ? 'text' : 'password'">
        <button
            type="button"
            class="absolute inset-y-0 end-0 flex w-10 items-center justify-center rounded-e-md text-slate-400 hover:text-slate-700 focus:outline-none focus-visible:text-brand-700"
            @click="visible = ! visible"
            aria-label="Show password"
            :aria-label="visible ? 'Hide password' : 'Show password'"
            :aria-pressed="visible.toString()"
            :title="visible ? 'Hide password' : 'Show password'"
            @disabled($disabled)
        >
            <x-icon name="bx-show" class="text-lg" x-show="! visible" />
            <x-icon name="bx-hide" class="text-lg" x-show="visible" x-cloak />
        </button>
    </div>
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => $fieldClasses]) }}>
@endif
