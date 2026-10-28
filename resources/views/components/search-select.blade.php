@props([
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Type to search',
    'required' => false,
    'emptyLabel' => null,
    'model' => null,
    'xName' => null,
    'xId' => null,
])

{{--
    A searchable dropdown for long lists. Each option is ['value' => ..., 'label' => ..., 'hint' => ?, 'group' => ?].
    Use `model` to keep the value in a parent Alpine property, and `x-name` / `x-id` for names built in an x-for loop.
--}}
@php
    $options = collect($options)->map(fn ($option) => [
        'value' => (string) $option['value'],
        'label' => (string) $option['label'],
        'hint' => isset($option['hint']) ? (string) $option['hint'] : null,
        'group' => isset($option['group']) ? (string) $option['group'] : null,
    ])->values()->all();
@endphp

<div
    {{ $attributes->class('relative') }}
    x-data="searchSelect({ options: @js($options), value: @js($selected === null ? '' : (string) $selected), emptyLabel: @js($emptyLabel) })"
    x-modelable="value"
    @if ($model) x-model="{{ $model }}" @endif
    @click.outside="if (isOpen) close()"
>
    <input type="hidden" x-ref="hidden" :value="value"
        @if ($name) name="{{ $name }}" @endif
        @if ($xName) :name="{{ $xName }}" @endif>
    <input
        type="text"
        @if ($id) id="{{ $id }}" @endif
        @if ($xId) :id="{{ $xId }}" @endif
        x-model="query"
        @focus="open(); $el.select()"
        @click="open()"
        @input="open(); active = 0"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.enter.prevent="isOpen ? choose(filtered[active]) : open()"
        @keydown.escape="close()"
        @keydown.tab="close()"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        role="combobox"
        :aria-expanded="isOpen"
        @required($required)
        class="mt-1 block w-full rounded-md border-transparent bg-slate-100/70 text-sm focus:border-brand-600 focus:bg-white focus:ring-0"
    >
    <ul x-ref="list" x-show="isOpen" x-cloak role="listbox" class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200">
        <template x-for="(option, index) in filtered" :key="option.value">
            <li role="option" :aria-selected="option.value === value" :data-active="index === active">
                <button type="button" tabindex="-1" class="flex w-full items-baseline justify-between gap-3 px-3 py-2 text-left"
                    :class="index === active ? 'bg-brand-50 text-brand-800' : (option.value === value ? 'font-semibold' : 'hover:bg-slate-50')"
                    @mousedown.prevent="choose(option)" @mouseenter="active = index">
                    <span x-text="option.label"></span>
                    <span class="shrink-0 text-xs text-slate-500" x-show="option.hint || option.group" x-text="[option.group, option.hint].filter(Boolean).join(' · ')"></span>
                </button>
            </li>
        </template>
        <li x-show="filtered.length === 0" class="px-3 py-2 text-slate-500">No matches</li>
    </ul>
</div>
