@php
    $navClass = function (array $item): string {
        $base = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm';

        return request()->routeIs($item['pattern'])
            ? $base.' bg-brand-50 font-semibold text-brand-800'
            : $base.' text-slate-600 hover:bg-slate-50';
    };
@endphp

<aside
    class="no-print fixed inset-y-0 start-0 z-30 flex w-64 flex-col border-e border-slate-200 bg-white lg:flex"
    :class="sidebar ? 'flex' : 'hidden lg:flex'"
>
    <div class="px-5 py-5">
        <a href="{{ auth()->user()->role === \App\Enums\Role::PlatformAdmin ? route('platform.facilities.index') : route('dashboard') }}" class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white">
                <x-icon name="bx-plus-medical" class="text-xl" />
            </span>
            <span class="text-lg font-semibold tracking-tight text-slate-900">AfyaDesk</span>
        </a>
    </div>

    <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 pb-4">
        @if (auth()->user()->role === \App\Enums\Role::PlatformAdmin)
            <a href="{{ route('platform.facilities.index') }}" @click="sidebar = false" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs('platform.*') ? 'bg-brand-50 font-semibold text-brand-800' : 'text-slate-600 hover:bg-slate-50' }}">
                <x-icon name="bx-buildings" class="text-lg" />
                <span>Facilities</span>
            </a>
        @endif

        @foreach ($navigation['primary'] as $item)
            <a href="{{ route($item['route']) }}" @click="sidebar = false" class="{{ $navClass($item) }}">
                <x-icon :name="$item['icon']" class="text-lg" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        @foreach ($navigation['groups'] as $group)
            <p class="mb-1 mt-5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $group['label'] }}</p>
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['route']) }}" @click="sidebar = false" class="{{ $navClass($item) }}">
                    <x-icon :name="$item['icon']" class="text-lg" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="border-t border-slate-200 p-3">
        @foreach ($navigation['footer'] as $item)
            <a href="{{ route($item['route']) }}" @click="sidebar = false" class="{{ $navClass($item) }}">
                <x-icon :name="$item['icon']" class="text-lg" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <a href="{{ route('profile.edit') }}" @click="sidebar = false" class="{{ $navClass(['pattern' => 'profile.*', 'route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'bx-cog', 'permission' => '', 'group' => null]) }}">
            <x-icon name="bx-cog" class="text-lg" />
            <span>Settings</span>
        </a>
    </div>
</aside>
