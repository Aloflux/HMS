<x-app-layout>
    @php
        $signUpTotal = $signUps->sum('count');
        $signUpPeak = max(1, $signUps->max('count'));
        $kindPeak = max(1, $byKind->max() ?? 1);
        $occupancy = $stats['bedsInService'] > 0 ? (int) round($stats['bedsOccupied'] / $stats['bedsInService'] * 100) : 0;
        $attentionCount = $settingUp->count() + $withoutAdmin->count() + $inactive->count();
        $initials = fn (string $name): string => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-page-title>Platform overview</x-page-title>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-brand-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        {{ $stats['active'] }} live
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-500">Every facility on Tunzuri at a glance. Counts only; patient records stay inside each facility.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('platform.facilities.index') }}" class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card hover:shadow-card-hover">
                    <x-icon name="bx-buildings" class="text-lg text-brand-700" />
                    All facilities
                </a>
                <a href="{{ route('platform.facilities.index', ['provision' => 1]) }}" class="inline-flex items-center gap-2 rounded-md bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-brand hover:bg-brand-800">
                    <x-icon name="bx-plus" class="text-lg" />
                    Provision facility
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Facilities</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-buildings" class="text-xl" /></span>
            </div>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold tracking-tight text-slate-900">{{ $stats['facilities'] }}</span>
                <span class="text-sm text-slate-400">{{ $stats['active'] }} active</span>
            </p>
            <p class="mt-auto pt-4 text-sm text-slate-500">
                <span class="font-semibold text-brand-700">+{{ $stats['newThisMonth'] }}</span> joined this month
            </p>
        </div>

        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Setting up</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $stats['settingUp'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}"><x-icon name="bx-loader-circle" class="text-xl" /></span>
            </div>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ $stats['settingUp'] }}</p>
            <p class="mt-auto pt-4 text-sm text-slate-500">Self sign-ups not live yet</p>
        </div>

        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Visits today</p>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand-700"><x-icon name="bx-pulse" class="text-xl" /></span>
            </div>
            <p class="mt-1 text-4xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['visitsToday']) }}</p>
            <p class="mt-auto pt-4 text-sm text-slate-500">{{ number_format($stats['visits30d']) }} in the last 30 days · {{ number_format($stats['patients']) }} patients</p>
        </div>

        <div class="flex flex-col rounded-lg bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Bed occupancy</p>
                <span class="rounded-md px-2 py-1 text-[11px] font-semibold {{ $occupancy >= 90 ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-800' }}">{{ number_format($stats['users']) }} staff</span>
            </div>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold tracking-tight text-slate-900">{{ $occupancy }}%</span>
                <span class="text-sm text-slate-400">{{ $stats['bedsOccupied'] }} / {{ $stats['bedsInService'] }} beds</span>
            </p>
            <div class="mt-auto pt-4">
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $occupancy >= 90 ? 'bg-red-600' : 'bg-brand-600' }}" style="width: {{ min($occupancy, 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Trend and mix --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <section class="rounded-lg bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">New facilities per week</h2>
                    <p class="text-sm text-slate-500">Last {{ $signUps->count() }} weeks, by the Monday each week starts</p>
                </div>
                <p class="text-right">
                    <span class="block text-2xl font-extrabold tracking-tight text-slate-900">{{ $signUpTotal }}</span>
                    <span class="block text-xs text-slate-400">joined in this period</span>
                </p>
            </div>

            <div class="mt-6 flex h-44 items-end gap-2 border-b border-slate-200" role="img" aria-label="New facilities per week: {{ $signUps->map(fn ($week) => $week['label'].' '.$week['count'])->join(', ') }}">
                @foreach ($signUps as $week)
                    @php($barHeight = $week['count'] > 0 ? max(6, (int) round($week['count'] / $signUpPeak * 85)) : 2)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                        @if (($loop->last || $week['count'] === $signUps->max('count')) && $week['count'] > 0)
                            <span class="mb-1 text-xs font-semibold tabular-nums text-slate-700">{{ $week['count'] }}</span>
                        @endif
                        <div class="w-full max-w-10 rounded-t {{ $week['count'] > 0 ? 'bg-brand-600 group-hover:bg-brand-700' : 'bg-slate-200' }}" style="height: {{ $barHeight }}%"></div>
                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2.5 py-1.5 text-xs text-white shadow-float group-hover:block">
                            <span class="font-semibold">{{ $week['count'] }} {{ str('facility')->plural($week['count']) }}</span>
                            <span class="text-slate-300">· week of {{ $week['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex gap-2">
                @foreach ($signUps as $week)
                    <span class="flex-1 text-center text-[11px] text-slate-400">{{ $week['label'] }}</span>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg bg-white p-5 shadow-card">
            <h2 class="font-bold text-slate-900">Facilities by type</h2>
            <p class="text-sm text-slate-500">All {{ $stats['facilities'] }} facilities</p>
            <ul class="mt-5 space-y-4">
                @forelse ($byKind as $kind => $count)
                    <li class="text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-700">{{ $kinds[$kind] ?? ucfirst(str_replace('_', ' ', $kind)) }}</span>
                            <span class="font-semibold text-slate-900">{{ $count }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ (int) round($count / $kindPeak * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No facilities yet.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Attention and recent --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-lg bg-white shadow-card">
            <div class="flex items-center justify-between gap-3 p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $attentionCount > 0 ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}">
                        <x-icon name="{{ $attentionCount > 0 ? 'bx-error' : 'bx-check-shield' }}" class="text-xl" />
                    </span>
                    <div>
                        <h2 class="font-bold text-slate-900">Needs attention</h2>
                        <p class="text-sm text-slate-500">{{ $attentionCount > 0 ? $attentionCount.' '.str('item')->plural($attentionCount) : 'Nothing waiting on you' }}</p>
                    </div>
                </div>
            </div>
            <ul class="space-y-2 px-5 pb-5">
                @foreach ($settingUp as $facility)
                    @php($owner = $owners[$facility->id] ?? null)
                    <li class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-3">
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-semibold text-slate-900">{{ $facility->name }}</span>
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-amber-800">Setting up</span>
                            </span>
                            <span class="block truncate text-xs text-slate-500">
                                Signed up {{ $facility->created_at?->diffForHumans() }}
                                @if ($owner)
                                    · {{ $owner->email }} · {{ $owner->hasVerifiedEmail() ? 'email verified' : 'email not verified' }}
                                @endif
                            </span>
                        </span>
                        <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="shrink-0 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-card hover:shadow-card-hover">Owner</a>
                    </li>
                @endforeach
                @foreach ($withoutAdmin as $facility)
                    <li class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-3">
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-semibold text-slate-900">{{ $facility->name }}</span>
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-red-700">No active admin</span>
                            </span>
                            <span class="block text-xs text-slate-500">Nobody can manage staff or prices here.</span>
                        </span>
                        <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="shrink-0 rounded-md bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white shadow-brand hover:bg-brand-800">Add admin</a>
                    </li>
                @endforeach
                @foreach ($inactive as $facility)
                    <li class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-3">
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-sm font-semibold text-slate-900">{{ $facility->name }}</span>
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">Inactive</span>
                            </span>
                            <span class="block text-xs text-slate-500">Staff cannot sign in.</span>
                        </span>
                        <form method="POST" action="{{ route('platform.facilities.update', $facility) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="1">
                            <button type="submit" class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 shadow-card hover:shadow-card-hover">Reactivate</button>
                        </form>
                    </li>
                @endforeach
                @if ($attentionCount === 0)
                    <li class="rounded-md bg-slate-50 px-3 py-6 text-center text-sm text-slate-500">Every facility is live, active and has an administrator.</li>
                @endif
            </ul>
        </section>

        <section class="rounded-lg bg-white shadow-card">
            <div class="flex items-center justify-between gap-3 p-5">
                <div>
                    <h2 class="font-bold text-slate-900">Newest facilities</h2>
                    <p class="text-sm text-slate-500">Latest to join the platform</p>
                </div>
                <a href="{{ route('platform.facilities.index', ['sort' => 'newest']) }}" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                    View all
                    <x-icon name="bx-right-arrow-alt" class="text-lg" />
                </a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recent as $facility)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-50 text-xs font-bold text-brand-800">{{ $initials($facility->name) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $facility->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $facility->code }} · {{ $kinds[$facility->kind] ?? $facility->kind }} · {{ $facility->district }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-slate-400">{{ $facility->created_at?->format('d M Y') }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-sm text-slate-500">No facilities yet.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Activity --}}
    <section class="mt-6 overflow-hidden rounded-lg bg-white shadow-card">
        <div class="flex items-center justify-between gap-3 p-5">
            <div>
                <h2 class="font-bold text-slate-900">Most active facilities</h2>
                <p class="text-sm text-slate-500">Ranked by visits opened in the last 30 days</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-left text-[11px] uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Facility</th>
                        <th class="px-5 py-3 text-right font-semibold">Visits · 30 days</th>
                        <th class="px-5 py-3 text-right font-semibold">Patients</th>
                        <th class="px-5 py-3 font-semibold">Bed occupancy</th>
                        <th class="px-5 py-3 text-right font-semibold">Staff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($busiest as $facility)
                        @php($facilityOccupancy = $facility->beds_in_service > 0 ? (int) round($facility->beds_occupied / $facility->beds_in_service * 100) : 0)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('platform.facilities.admins.index', $facility) }}" class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-50 text-xs font-bold text-brand-800">{{ $initials($facility->name) }}</span>
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $facility->name }}</span>
                                        <span class="block text-xs text-slate-400">{{ $facility->code }} · {{ $facility->district }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold tabular-nums text-slate-900">{{ number_format($facility->visits_30d) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-slate-600">{{ number_format($facility->patients_total) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $facilityOccupancy >= 90 ? 'bg-red-600' : 'bg-brand-600' }}" style="width: {{ min($facilityOccupancy, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs tabular-nums text-slate-500">{{ $facilityOccupancy }}% · {{ $facility->beds_occupied }}/{{ $facility->beds_in_service }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums text-slate-600">{{ $facility->staff_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No active facilities yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
