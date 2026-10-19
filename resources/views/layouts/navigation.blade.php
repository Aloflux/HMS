<aside
    class="no-print fixed inset-y-0 start-0 z-30 w-64 overflow-y-auto border-e border-brand-900 bg-brand-900 text-brand-50 lg:block"
    :class="sidebar ? 'block' : 'hidden lg:block'"
>
    <div class="px-5 py-6">
        <a href="{{ auth()->user()->role === \App\Enums\Role::PlatformAdmin ? route('platform.facilities.index') : route('dashboard') }}" class="flex items-center gap-2 text-lg font-semibold tracking-tight">
            <x-icon name="bx-plus-medical" class="text-2xl text-brand-200" />
            <span>AfyaDesk</span>
        </a>
        <p class="mt-1 text-xs text-brand-200">Hospital operations</p>
    </div>
    <nav class="flex flex-col gap-1 px-3 pb-8">
        @if (auth()->user()->role === \App\Enums\Role::PlatformAdmin)
            <a href="{{ route('platform.facilities.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm {{ request()->routeIs('platform.*') ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                <x-icon name="bx-buildings" class="text-lg" />
                <span>Facilities</span>
            </a>
        @endif
        @foreach ($navigation as $item)
            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm {{ request()->routeIs($item['pattern']) ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-800' }}">
                <x-icon :name="$item['icon']" class="text-lg" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
