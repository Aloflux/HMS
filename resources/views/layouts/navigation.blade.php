@php
    $navClass = function (array $item): string {
        $base = 'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition';

        return request()->routeIs($item['pattern'])
            ? $base.' bg-brand-700 font-semibold text-white shadow-brand'
            : $base.' font-medium text-slate-600 hover:bg-slate-100/80 hover:text-slate-900';
    };

    $navIconClass = fn (array $item): string => request()->routeIs($item['pattern'])
        ? 'text-lg text-white'
        : 'text-lg text-slate-400 group-hover:text-brand-700';

    $initials = collect(preg_split('/\s+/', trim(auth()->user()->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $roleLabel = auth()->user()->role === \App\Enums\Role::Staff ? auth()->user()->accessAreas() : auth()->user()->role->label();
@endphp

<aside
    class="no-print fixed inset-y-0 start-0 z-30 flex w-64 flex-col bg-white shadow-card lg:flex"
    :class="sidebar ? 'flex' : 'hidden lg:flex'"
>
    <div class="mb-3 flex h-16 shrink-0 items-center justify-between px-5">
        <a href="{{ auth()->user()->role === \App\Enums\Role::PlatformAdmin ? route('platform.dashboard') : route('dashboard') }}" class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-700 text-white shadow-brand">
                <x-icon name="bx-plus-medical" class="text-xl" />
            </span>
            <span>
                <span class="block text-lg font-bold leading-tight tracking-tight text-slate-900">AfyaDesk</span>
                <span class="block text-[10px] font-semibold uppercase tracking-widest text-brand-700">Clinical workspace</span>
            </span>
        </a>
        <span class="h-2 w-2 rounded-full bg-brand-500" aria-hidden="true"></span>
    </div>

    @if ($currentFacility)
        <div class="mx-4 mb-2 flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2.5">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                <x-icon name="bx-buildings" class="text-lg" />
            </span>
            <span class="min-w-0">
                <span class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Active facility</span>
                <span class="block truncate text-sm font-semibold text-slate-800">{{ $currentFacility->name }}</span>
            </span>
        </div>
    @endif

    <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-4 pb-4">
        @if (auth()->user()->role === \App\Enums\Role::PlatformAdmin)
            <p class="mb-1 mt-3 px-3 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Platform</p>
            @foreach ([['platform.dashboard', 'platform.dashboard', 'bx-grid-alt', 'Overview'], ['platform.facilities.index', 'platform.facilities.*', 'bx-buildings', 'Facilities']] as [$platformRoute, $platformPattern, $platformIcon, $platformLabel])
                <a href="{{ route($platformRoute) }}" @click="sidebar = false" class="{{ $navClass(['pattern' => $platformPattern]) }}">
                    <x-icon :name="$platformIcon" class="{{ $navIconClass(['pattern' => $platformPattern]) }}" />
                    <span>{{ $platformLabel }}</span>
                </a>
            @endforeach
        @endif

        @if ($navigation['primary'] !== [])
            <p class="mb-1 mt-3 px-3 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Workspace</p>
        @endif
        @foreach ($navigation['primary'] as $item)
            <a href="{{ route($item['route']) }}" @click="sidebar = false" class="{{ $navClass($item) }}">
                <x-icon :name="$item['icon']" class="{{ $navIconClass($item) }}" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        @foreach ($navigation['groups'] as $group)
            <p class="mb-1 mt-5 px-3 text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ $group['label'] }}</p>
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['route']) }}" @click="sidebar = false" class="{{ $navClass($item) }}">
                    <x-icon :name="$item['icon']" class="{{ $navIconClass($item) }}" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="p-4">
        <div class="rounded-lg bg-slate-50 p-2">
            @foreach ($navigation['footer'] as $item)
                <a href="{{ route($item['route']) }}" @click="sidebar = false" class="{{ $navClass($item) }}">
                    <x-icon :name="$item['icon']" class="{{ $navIconClass($item) }}" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            <div class="flex items-center gap-3 rounded-md bg-white px-3 py-2.5 shadow-card">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">{{ $initials }}</span>
                <a href="{{ route('profile.edit') }}" @click="sidebar = false" class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-slate-400">{{ $roleLabel }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Sign out">
                        <x-icon name="bx-log-out" class="text-lg" />
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
