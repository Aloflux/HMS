<x-guest-layout plain>
    @php($initials = fn (string $name): string => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode(''))

    <div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-700 text-white shadow-brand">
                <x-icon name="bx-plus-medical" class="text-xl" />
            </span>
            <span class="text-lg font-bold tracking-tight text-slate-900">Tunzuri</span>
        </div>

        <h1 class="mt-8 text-2xl font-extrabold tracking-tight text-slate-900">Where are you working?</h1>
        <p class="mt-1 text-sm text-slate-500">You work at more than one facility. You can switch at any time from the sidebar.</p>

        <ul class="mt-6 grid gap-3">
            @foreach ($memberships as $membership)
                <li>
                    <form method="POST" action="{{ route('facilities.switch', $membership) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg bg-white p-4 text-start shadow-card transition hover:shadow-card-hover">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-xs font-bold text-brand-800">{{ $initials($membership->facility->name) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-slate-900">{{ $membership->facility->name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $membership->role->label() }} · {{ $membership->accessAreas() }}</span>
                            </span>
                            @if ($membership->id === $current)
                                <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand-800">Current</span>
                            @else
                                <x-icon name="bx-right-arrow-alt" class="shrink-0 text-xl text-slate-400" />
                            @endif
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-800">Sign out</button>
        </form>
    </div>
</x-guest-layout>
